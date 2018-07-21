<?php

namespace Drupal\Dev\marvin\Composer;

use Composer\IO\IOInterface;
use Composer\Semver\Comparator;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Sweetchuck\GitHooks\Composer\Scripts as GitHooks;

class Scripts {

  /**
   * Current event.
   *
   * @var \Composer\Script\Event
   */
  protected static $event;

  /**
   * CLI process callback.
   *
   * @var \Closure
   */
  protected static $processCallbackWrapper;

  /**
   * @var string
   */
  protected static $drushSutRoot = 'tests/fixtures/drush-sut';

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected static $fs;

  public static function preInstallCmd(Event $event): int {
    static::init($event);
    static::checkComposerVersion();

    return 0;
  }

  /**
   * Composer event callback.
   */
  public static function postInstallCmd(Event $event): int {
    static::init($event);
    //static::gitHooksDeploy();
    //static::phpcsConfigSet();
    static::prepareDrushSut();

    return 0;
  }

  public static function preUpdateCmd(Event $event): int {
    static::init($event);
    static::checkComposerVersion();

    return 0;
  }

  /**
   * Composer event callback.
   */
  public static function postUpdateCmd(Event $event): int {
    static::init($event);
    //static::gitHooksDeploy();
    //static::phpcsConfigSet();
    static::prepareDrushSut();

    return 0;
  }

  protected static function init(Event $event) {
    static::$event = $event;
    static::$fs = new Filesystem();

    if (!static::$processCallbackWrapper) {
      static::$processCallbackWrapper = function (string $type, string $buffer) {
        static::processCallback($type, $buffer);
      };
    }
  }

  protected static function gitHooksDeploy(): void {
    if (!static::$event->isDevMode()) {
      return;
    }

    GitHooks::deploy(static::$event);
  }

  protected static function phpcsConfigSet(): void {
    if (!static::$event->isDevMode()) {
      return;
    }

    /** @var \Composer\Config $config */
    $config = static::$event->getComposer()->getConfig();
    $cmdPattern = '%s --config-set installed_paths %s';
    $cmdArgs = [
      escapeshellcmd($config->get('bin-dir') . '/phpcs'),
      escapeshellarg($config->get('vendor-dir') . '/drupal/coder/coder_sniffer'),
    ];

    static::processRun('.', vsprintf($cmdPattern, $cmdArgs));
  }

  protected static function preparePhpunitXml(): void {
    $dstFileName = 'phpunit.xml';
    if (!static::$event->isDevMode() || static::$fs->exists($dstFileName)) {
      return;
    }

    $srcFileName = 'phpunit.xml.dist';
    $content = static::fileGetContents($srcFileName);

    $cwd = getcwd();
    $replacementPairs = [];

    $envUnishDbUrl = getenv('UNISH_DB_URL');
    if (!$envUnishDbUrl) {
      $envUnishDbUrl = 'sqlite://none/of/this/matters';
      $placeholder = '<!--<env name="UNISH_DB_URL" value="sqlite://none/of/this/matters"/>-->';
      $new = sprintf('<env name="UNISH_DB_URL" value="%s"/>', static::escapeXmlAttribute($envUnishDbUrl));
      $replacementPairs[$placeholder] = $new;
    }

    $envUnishTmp = getenv('UNISH_TMP');
    if (!$envUnishTmp) {
      $envUnishTmp = "$cwd/tests/fixtures";
      $placeholder = '<!--<env name="UNISH_TMP" value="/tmp" />-->';
      $new = sprintf('<env name="UNISH_TMP" value="%s" />', static::escapeXmlAttribute($envUnishTmp));
      $replacementPairs[$placeholder] = $new;
    }

    $envUnishDrush = getenv('UNISH_Drush');
    if (!$envUnishDrush) {
      $envUnishDrush = "$cwd/tests/fixtures";
      $placeholder = '<!--<env name="UNISH_DRUSH" value="./bin/drush"/>-->';
      $new = sprintf('<env name="UNISH_DRUSH" value="%s"/>', static::escapeXmlAttribute($envUnishDrush));
      $replacementPairs[$placeholder] = $new;
    }

    static::$fs->dumpFile($dstFileName, strtr($content, $replacementPairs));
  }

  protected static function prepareDrushSut(): void {
    if (!static::$event->isDevMode()) {
      return;
    }

    static::prepareDrushSutMarvin();
    static::prepareDrushSutDirs();
  }

  protected static function prepareDrushSutMarvin(): void {
    $dstDir = 'tests/fixtures/drush-sut/drush/custom/marvin';

    $relative = implode(
      '/',
      array_fill(
        0,
        substr_count($dstDir, '/') + 1,
        '..'
      )
    );

    $filesToSymLink = [
      'Commands',
      'etc',
      'src',
      'composer.json',
      'drush9.services.yml',
    ];

    if (!static::$fs->exists($dstDir)) {
      static::$fs->mkdir($dstDir);
    }

    foreach ($filesToSymLink as $fileToSymLink) {
      $target = "$dstDir/$fileToSymLink";
      if (!static::$fs->exists($target)) {
        static::$fs->symlink("$relative/$fileToSymLink", $target);
      }
    }
  }

  protected static function prepareDrushSutDirs(): void {
    $drushSutRoot = static::$drushSutRoot;

    $dirs = [
      "$drushSutRoot/web/libraries",
      "$drushSutRoot/web/profiles",
      "$drushSutRoot/web/themes",
    ];
    static::$fs->mkdir($dirs, 0777 - umask());
  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(): void {
    $composer = static::$event->getComposer();
    $io = static::$event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');

      return;
    }

    if (Comparator::lessThan($version, '1.0.0')) {
      $message = 'Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing';
      $io->writeError("<error>$message</error>.");

      throw new \Exception($message, 1);
    }

    $io->write("Composer version <info>$version</info> is fine", TRUE, IOInterface::VERBOSE);
  }

  protected static function processRun(string $workingDirectory, string $command): Process {
    static::$event->getIO()->write("Run '$command' in '$workingDirectory'");
    $process = new Process($command, NULL, NULL, NULL, 0);
    $process->setWorkingDirectory($workingDirectory);
    $process->run(static::$processCallbackWrapper);

    return $process;
  }

  protected static function processCallback(string $type, string $buffer): void {
    $type === Process::OUT ?
      static::$event->getIO()->write($buffer, FALSE)
      : static::$event->getIO()->writeError($buffer, FALSE);
  }

  protected static function escapeXmlAttribute(string $value): string {
    return htmlentities($value, ENT_QUOTES);
  }

  protected static function fileGetContents(string $fileName): string {
    $content = file_get_contents($fileName);
    if ($content === FALSE) {
      throw new \RuntimeException("File '$fileName' is not readable.", 1);
    }

    return $content;
  }

}

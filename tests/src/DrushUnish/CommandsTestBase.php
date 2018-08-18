<?php

namespace Drush\Commands\Tests\marvin\Unish;

use Symfony\Component\Finder\Finder;
use Unish\CommandUnishTestCase;
use Webmozart\PathUtil\Path;

abstract class CommandsTestBase extends CommandUnishTestCase {

  /**
   * @var string
   */
  protected static $selfDir = '';

  /**
   * @var string
   */
  protected static $binDir = 'bin';

  /**
   * {@inheritdoc}
   */
  public static function getDrush() {
    return Path::join(static::getSelfDir(), 'bin', 'drush');
  }

  protected static function getSelfDir(): string {
    if (static::$selfDir === '') {
      static::$selfDir = Path::canonicalize(Path::join(__DIR__, '..', '..', '..'));
    }

    return static::$selfDir;
  }

  protected static function getExtensionsDir(): string {
    return static::getTmp() . '/extensions';
  }

  /**
   * @return \Symfony\Component\Finder\Finder|\Symfony\Component\Finder\SplFileInfo[]
   */
  protected static function getExtensionDirs(): Finder {
    return (new Finder())
      ->in(static::getExtensionsDir())
      ->directories()
      ->depth('== 0');
  }

  public function installDrupal($env = 'dev', $install = FALSE, $withCoverage = FALSE) {
    $root = $this->webroot();
    $uri = $env;
    $site = "$root/sites/$uri";

    // If specified, install Drupal as a multi-site.
    if ($install) {
      $options = [
        'root' => $root,
        'db-url' => $this->dbUrl($env),
        'sites-subdir' => $uri,
        'yes' => NULL,
        'quiet' => NULL,
      ];
      $this->drush(
        'site-install',
        ['testing', 'install_configure_form.enable_update_status_emails=NULL'],
        $options,
        NULL,
        NULL,
        self::EXIT_SUCCESS,
        NULL,
        [],
        $withCoverage
      );
      // Give us our write perms back.
      chmod($site, 0777);
    }
    else {
      $this->mkdir($site);
      touch("$site/settings.php");
    }
  }

  /**
   * {@inheritdoc}
   *
   * Replace self::getDrush() with static::getDrush().
   * Support array values for --include.
   * Use the same PHP executable.
   */
  public function drush(
    $command,
    array $args = [],
    array $options = [],
    $site_specification = NULL,
    $cd = NULL,
    $expected_return = self::EXIT_SUCCESS,
    $suffix = NULL,
    $env = [],
    $withCoverage = TRUE
  ) {
    $sites = static::getSites();

    // Cd is added for the benefit of siteSshTest which tests a strict command.
    $global_option_list = [
      'simulate',
      'root',
      'uri',
      'include',
      'config',
      'alias-path',
      'ssh-options',
      'backend',
      'cd',
    ];

    $options += ['uri' => 'http://' . key($sites)];
    $hide_stderr = FALSE;
    $cmd = [
      $this->getPhpExecutable(),
      static::getDrush(),
    ];

    // Insert global options.
    foreach ($options as $key => $value) {
      if (in_array($key, $global_option_list)) {
        unset($options[$key]);
        if ($key == 'backend') {
          $hide_stderr = TRUE;
          $value = NULL;
        }

        if ($key == 'uri' && $value == 'OMIT') {
          continue;
        }

        if (!isset($value)) {
          $cmd[] = "--$key";
        }
        else {
          if (!is_array($value)) {
            $value = [$value];
          }

          foreach ($value as $v) {
            $cmd[] = "--$key=" . static::escapeshellarg($v);
          }
        }
      }
    }

    if ($level = $this->logLevel()) {
      $cmd[] = '--' . $level;
    }
    $cmd[] = "--no-interaction";

    // Insert code coverage argument before command, in order for it to be
    // parsed as a global option. This matters for commands like ssh and rsync
    // where options after the command are passed along to external commands.
    $result = $this->getTestResultObject();
    if ($withCoverage && $result->getCollectCodeCoverageInformation()) {
      $coverage_file = tempnam($this->getTmp(), 'drush_coverage');
      if ($coverage_file) {
        $cmd[] = "--drush-coverage=" . $coverage_file;
      }
    }

    // Insert site specification and drush command.
    $cmd[] = empty($site_specification)
      ? NULL
      : static::escapeshellarg($site_specification);

    $cmd[] = $command;

    // Insert drush command arguments.
    foreach ($args as $arg) {
      $cmd[] = static::escapeshellarg($arg);
    }

    // Insert drush command options.
    foreach ($options as $key => $value) {
      if (!isset($value)) {
        $cmd[] = "--$key";
      }
      else {
        $cmd[] = "--$key=" . static::escapeshellarg($value);
      }
    }

    $cmd[] = $suffix;
    if ($hide_stderr) {
      $cmd[] = '2>' . $this->bitBucket();
    }

    // Remove NULLs.
    $exec = array_filter($cmd, 'strlen');

    // Set sendmail_path to 'true' to disable any outgoing emails
    // that tests might cause Drupal to send.
    $php_options = (array_key_exists('PHP_OPTIONS', $env)) ?
      $env['PHP_OPTIONS'] . ' ' : '';

    // @todo The PHP Options below are not yet honored by execute().
    // See .travis.yml for an alternative way.
    $env['PHP_OPTIONS'] = "{$php_options}-d sendmail_path='true'";
    $cmd = implode(' ', $exec);
    $return = $this->execute($cmd, $expected_return, $cd, $env);

    // Save code coverage information.
    if (!empty($coverage_file)) {
      $data = unserialize(file_get_contents($coverage_file));
      unlink($coverage_file);
      // Save for appending after the test finishes.
      $this->coverage_data[] = $data;
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if (!$this->getSites()) {
      $this->setUpDrupal(1, $this->setUpDrupalNeedsToBeInstalled());
    }

    parent::setUp();
    $this->deleteTestArtifacts();
  }

  protected function setUpDrupalNeedsToBeInstalled(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->deleteTestArtifacts();
    parent::tearDown();
  }

  /**
   * @return $this
   */
  protected function deleteTestArtifacts() {
    return $this;
  }

  protected function getDefaultDrushCommandOptions(): array {
    return [
      'root' => $this->webroot(),
      'uri' => key(static::getSites()),
      'yes' => NULL,
      'no-ansi' => NULL,
      'config' => Path::join(static::getSut(), 'drush'),
    ];
  }

  protected function getPhpExecutable(): string {
    // @todo Make it configurable through environment variable.
    return PHP_BINDIR . '/php';
  }

}

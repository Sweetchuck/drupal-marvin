<?php

namespace Drupal\marvin\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class Scripts {

  /**
   * @var string[]
   */
  protected static $drushConfigDirs = [
    'drush/contrib/marvin/Commands',
    'drush',
  ];

  /**
   * @var string[]
   */
  protected static $drushIncludeDirs = [];

  /**
   * @var \Composer\Script\Event
   */
  protected static $event;

  /**
   * @var \Closure
   */
  protected static $processCallbackWrapper;

  public static function postInstallCmd(Event $event): bool {
    static::init($event);

    return static::forwardComposerHookToDrushCommand('post-install-cmd');
  }

  public static function postUpdateCmd(Event $event): bool {
    static::init($event);

    return static::forwardComposerHookToDrushCommand('post-update-cmd');
  }

  /**
   * @todo Probably this whole class is not necessary for "marvin_product",
   * because this can be implemented via
   * composer.json#scripts.post-install-cmd = drush marvin:composer:X.
   */
  protected static function forwardComposerHookToDrushCommand(string $hook): bool {
    $composerConfig = static::$event->getComposer()->getConfig();
    $binDirAbs = $composerConfig->get('bin-dir');
    $binDir = Path::makeRelative($binDirAbs, getcwd());
    $cmdPattern = '%s';
    $cmdArgs = [
      escapeshellcmd("$binDir/drush"),
    ];

    if (static::$event->getIO()->isDecorated()) {
      $cmdPattern .= ' --ansi';
    }

    foreach (static::getDrushConfigDirs() as $drushConfigDir) {
      $cmdPattern .= ' --config=%s';
      $cmdArgs[] = escapeshellarg($drushConfigDir);
    }

    foreach (static::getDrushIncludeDirs() as $drushIncludeDir) {
      $cmdPattern .= ' --include=%s';
      $cmdArgs[] = escapeshellarg($drushIncludeDir);
    }

    $cmdPattern .= ' %s';
    $cmdArgs[] = escapeshellcmd("marvin:composer:$hook");

    if (static::$event->isDevMode()) {
      $cmdPattern .= ' --dev-mode';
    }

    $process = new Process(vsprintf($cmdPattern, $cmdArgs));

    $exitCode = $process->run(static::$processCallbackWrapper);

    return $exitCode === 0;
  }

  protected static function init(Event $event): void {
    static::$event = $event;
    static::$processCallbackWrapper = function (string $type, string $buffer) {
      static::processCallback($type, $buffer);
    };
  }

  protected static function getDrushConfigDirs(): array {
    // @todo Dynamically detect the install path from composer.json.
    return static::$drushConfigDirs;
  }

  protected static function getDrushIncludeDirs(): array {
    // @todo Dynamically detect the install path from composer.json.
    return static::$drushIncludeDirs;
  }

  protected static function getComposerBinDir(): string {
    return static::$event->getComposer()->getConfig()->get('bin-dir');
  }

  protected static function processCallback(string $type, string $buffer) {
    if ($type === Process::OUT) {
      static::$event->getIO()->write($buffer);

      return;
    }

    static::$event->getIO()->writeError($buffer);
  }

}

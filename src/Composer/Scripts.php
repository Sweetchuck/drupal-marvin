<?php

namespace Drush\marvin\Composer;

use Sweetchuck\GitHooks\Composer\Scripts as GitHooks;
use Composer\Script\Event;
use Symfony\Component\Process\Process;

class Scripts {

  /**
   * @var \Composer\Script\Event
   */
  protected static $event;

  /**
   * @var \Closure
   */
  protected static $processCallbackWrapper;

  public static function selfPostInstallCmd(Event $event): bool {
    static::init($event);
    GitHooks::deploy($event);
    static::phpcsSetConfigInstalledPaths();

    return TRUE;
  }

  public static function selfPostUpdateCmd(Event $event): bool {
    static::init($event);
    GitHooks::deploy($event);

    return TRUE;
  }

  public static function postInstallCmd(Event $event): bool {
    static::init($event);

    return static::forwardComposerHookToDrushCommand('post-install-cmd');
  }

  public static function postUpdateCmd(Event $event): bool {
    static::init($event);

    return static::forwardComposerHookToDrushCommand('post-update-cmd');
  }

  protected static function forwardComposerHookToDrushCommand(string $hook): bool {
    $composerConfig = static::$event->getComposer()->getConfig();
    $binDir = $composerConfig->get('bin-dir');
    $cmdPattern = '%s %s';
    $cmdArgs = [
      escapeshellcmd("$binDir/drush"),
      escapeshellcmd("marvin:composer:$hook"),
    ];
    $process = new Process(vsprintf($cmdPattern, $cmdArgs));
    $exitCode = $process->run(static::$processCallbackWrapper);

    return $exitCode === 0;
  }

  protected static function phpcsSetConfigInstalledPaths(): bool {
    $composerConfig = static::$event->getComposer()->getConfig();
    $binDir = $composerConfig->get('bin-dir');
    $vendorDir = $composerConfig->get('vendor-dir');
    $cmdPattern = '%s --config-set installed_paths %s';
    $cmdArgs = [
      escapeshellcmd("$binDir/phpcs"),
      escapeshellarg("$vendorDir/drupal/coder/coder_sniffer"),
    ];
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

  protected static function getComposerBinDir(): string {
    return static::$event->getComposer()->getConfig()->get('bin-dir');
  }

  protected static function processCallback(string $type, string $buffer) {
    if ($type === Process::OUT) {
      static::$event->getIO()->write($buffer);
    }
    else {
      static::$event->getIO()->writeError($buffer);
    }
  }

}

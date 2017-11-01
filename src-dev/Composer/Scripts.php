<?php

namespace Drupal\marvin\Composer;

use Sweetchuck\GitHooks\Composer\Scripts as GitHooks;
use Composer\Script\Event;
use Symfony\Component\Process\Process;

class Scripts
{
  /**
   * @var \Composer\Script\Event
   */
  protected static $event;

  /**
   * @var \Closure
   */
  protected static $processCallbackWrapper;

  public static function postInstallCmd(Event $event): bool
  {
    static::init($event);
    GitHooks::deploy($event);

    return true;
  }

  public static function postUpdateCmd(Event $event): bool
  {
    static::init($event);
    GitHooks::deploy($event);

    return true;
  }

  protected static function init(Event $event)
  {
    static::$event = $event;
    static::$processCallbackWrapper = function (string $type, string $buffer) {
      static::processCallback($type, $buffer);
    };
  }

  protected static function processCallback(string $type, string $buffer)
  {
    if ($type === Process::OUT) {
      static::$event->getIO()->write($buffer);
    } else {
      static::$event->getIO()->writeError($buffer);
    }
  }
}

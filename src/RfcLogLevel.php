<?php

declare(strict_types = 1);

namespace Drupal\marvin;

/**
 * RfcLogLevel from Drupal core is not always available.
 *
 * @see \Drupal\Core\Logger\RfcLogLevel
 * @see http://tools.ietf.org/html/rfc5424
 */
class RfcLogLevel {

  /**
   * Log message severity -- Emergency: system is unusable.
   *
   * @var int
   */
  const EMERGENCY = 0;

  /**
   * Log message severity -- Alert: action must be taken immediately.
   *
   * @var int
   */
  const ALERT = 1;

  /**
   * Log message severity -- Critical conditions.
   *
   * @var int
   */
  const CRITICAL = 2;

  /**
   * Log message severity -- Error conditions.
   *
   * @var int
   */
  const ERROR = 3;

  /**
   * Log message severity -- Warning conditions.
   *
   * @var int
   */
  const WARNING = 4;

  /**
   * Log message severity -- Normal but significant conditions.
   *
   * @var int
   */
  const NOTICE = 5;

  /**
   * Log message severity -- Informational messages.
   *
   * @var int
   */
  const INFO = 6;

  /**
   * Log message severity -- Debug-level messages.
   *
   * @var int
   */
  const DEBUG = 7;

  /**
   * An array with the severity levels as keys and labels as values.
   */
  protected static array $levels = [];

  /**
   * Returns a list of severity levels, as defined in RFC 5424.
   */
  public static function getLevels(): array {
    if (!static::$levels) {
      static::$levels = [
        static::EMERGENCY => dt('Emergency'),
        static::ALERT => dt('Alert'),
        static::CRITICAL => dt('Critical'),
        static::ERROR => dt('Error'),
        static::WARNING => dt('Warning'),
        static::NOTICE => dt('Notice'),
        static::INFO => dt('Info'),
        static::DEBUG => dt('Debug'),
      ];
    }

    return static::$levels;
  }

}

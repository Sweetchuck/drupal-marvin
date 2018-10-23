<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Helper;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @method $this getErrorOutput()
 */
class DummyOutput extends ConsoleOutput {

  /**
   * @var int
   */
  protected static $instanceCounter = 0;

  /**
   * @var string
   */
  public $output = '';

  /**
   * @var int
   */
  public $instanceId = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = NULL, OutputFormatterInterface $formatter = NULL, $isStdError = FALSE) {
    parent::__construct($verbosity, $decorated, $formatter);
    $this->instanceId = static::$instanceCounter++;

    $errorOutput = $isStdError ? $this : new static($verbosity, $decorated, $formatter, TRUE);
    $this->setErrorOutput($errorOutput);
  }

  /**
   * {@inheritdoc}
   */
  protected function doWrite($message, $newline) {
    $this->output .= $message . ($newline ? PHP_EOL : '');
  }

}

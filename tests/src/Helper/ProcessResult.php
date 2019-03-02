<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Helper;

use Symfony\Component\Process\Process;

class ProcessResult {

  /**
   * @var int
   */
  public $exitCode = 0;

  /**
   * @var string
   */
  public $stdOutput = '';

  /**
   * @var string
   */
  public $stdError = '';

  /**
   * @return static
   */
  public static function createFromProcess(Process $process) {
    $result = new static();
    $result->exitCode = $process->getExitCode();
    $result->stdOutput = $process->getOutput();
    $result->stdError = $process->getErrorOutput();

    return $result;
  }

}

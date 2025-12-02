<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Symfony\Component\Process\Process;

class ProcessFactory implements ProcessFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function createProcess(
    array $command,
    ?string $cwd = NULL,
    ?array $env = NULL,
    mixed $input = NULL,
    ?float $timeout = 60,
  ): Process {
    return new Process($command, $cwd, $env, $input, $timeout);
  }

}

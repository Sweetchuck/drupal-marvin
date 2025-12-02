<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Symfony\Component\Process\Process;

interface ProcessFactoryInterface {

  /**
   * @phpstan-param array<string> $command
   * @phpstan-param null|array<string, string> $env
   */
  public function createProcess(
    array $command,
    ?string $cwd = NULL,
    ?array $env = NULL,
    mixed $input = NULL,
    ?float $timeout = 60,
  ): Process;

}

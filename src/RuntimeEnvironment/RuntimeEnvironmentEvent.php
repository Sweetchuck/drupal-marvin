<?php

declare(strict_types=1);

namespace Drupal\marvin\RuntimeEnvironment;

use Symfony\Contracts\EventDispatcher\Event;

class RuntimeEnvironmentEvent extends Event {

  public const string EVENT_COLLECT = 'marvin.runtime_environment_list.collect';

  public const string EVENT_ALTER = 'marvin.runtime_environment_list.alter';

  /**
   * @phpstan-param \Drupal\marvin\RuntimeEnvironment\RuntimeEnvironmentList<string, marvin-runtime-environment-base> $runtimeEnvironmentList
   */
  public function __construct(
    public readonly RuntimeEnvironmentList $runtimeEnvironmentList,
  ) {
  }

}

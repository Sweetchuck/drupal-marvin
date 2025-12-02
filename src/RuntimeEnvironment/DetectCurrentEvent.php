<?php

declare(strict_types=1);

namespace Drupal\marvin\RuntimeEnvironment;

use Symfony\Contracts\EventDispatcher\Event;

class DetectCurrentEvent extends Event {

  public const string EVENT_DETECT_CURRENT = 'marvin.runtime_environment.detectCurrent';

  public ?string $currentId = NULL;

  public function __construct(
    public readonly RuntimeEnvironmentList $list,
  ) {
  }

}

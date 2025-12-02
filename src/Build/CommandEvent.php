<?php

declare(strict_types=1);

namespace Drupal\marvin\Build;

use Drupal\marvin\CommandEvent as BaseCommandEvent;

class CommandEvent extends BaseCommandEvent {

  public const string EVENT_BUILD_TASKS_COLLECT = 'marvin.build.tasks.collect';

  public const string EVENT_BUILD_TASKS_ALTER = 'marvin.build.tasks.alter';

}

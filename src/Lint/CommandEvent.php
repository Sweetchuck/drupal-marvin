<?php

declare(strict_types=1);

namespace Drupal\marvin\Lint;

use Drupal\marvin\CommandEvent as BaseCommandEvent;

class CommandEvent extends BaseCommandEvent {

  public const string EVENT_RUN_TASKS_COLLECT = 'marvin.lint.run.tasks.collect';

  public const string EVENT_RUN_TASKS_ALTER = 'marvin.lint.run.tasks.alter';

}

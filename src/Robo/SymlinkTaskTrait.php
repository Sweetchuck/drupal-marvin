<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\UpsertSymlinkTask;

trait SymlinkTaskTrait {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\UpsertSymlinkTask
   */
  protected function taskMarvinUpsertSymlink(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\UpsertSymlinkTask $task */
    $task = $this->task(UpsertSymlinkTask::class);
    $task->setOptions($options);

    return $task;
  }

}

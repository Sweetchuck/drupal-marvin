<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\PrepareDirectoryTask;

trait PrepareDirectoryTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\PrepareDirectoryTask
   */
  protected function taskMarvinPrepareDirectory(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\PrepareDirectoryTask $task */
    $task = $this->task(PrepareDirectoryTask::class);
    $task->setOptions($options);

    return $task;
  }

}

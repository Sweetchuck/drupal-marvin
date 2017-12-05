<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\CopyFilesTask;

trait CopyFilesTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\CopyFilesTask
   */
  protected function taskMarvinCopyFiles(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\CopyFilesTask $task */
    $task = $this->task(CopyFilesTask::class);
    $task->setOptions($options);

    return $task;
  }

}

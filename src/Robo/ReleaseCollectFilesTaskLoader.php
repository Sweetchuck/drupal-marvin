<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\ReleaseCollectFilesTask;

trait ReleaseCollectFilesTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\ReleaseCollectFilesTask
   */
  protected function taskMarvinReleaseCollectFiles(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\ReleaseCollectFilesTask $task */
    $task = $this->task(ReleaseCollectFilesTask::class);
    $task->setOptions($options);

    return $task;
  }

}

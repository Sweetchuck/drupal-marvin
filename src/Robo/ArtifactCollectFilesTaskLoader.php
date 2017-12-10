<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\ArtifactCollectFilesTask;

trait ArtifactCollectFilesTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\ArtifactCollectFilesTask
   */
  protected function taskMarvinArtifactCollectFiles(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\ArtifactCollectFilesTask $task */
    $task = $this->task(ArtifactCollectFilesTask::class);
    $task->setOptions($options);

    return $task;
  }

}

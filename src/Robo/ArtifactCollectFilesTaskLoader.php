<?php

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\ArtifactCollectFilesTask;

trait ArtifactCollectFilesTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\ArtifactCollectFilesTask
   */
  protected function taskMarvinArtifactCollectFiles(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\ArtifactCollectFilesTask $task */
    $task = $this->task(ArtifactCollectFilesTask::class);
    $task->setOptions($options);

    return $task;
  }

}

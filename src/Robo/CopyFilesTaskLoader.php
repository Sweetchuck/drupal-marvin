<?php

declare(strict_types = 1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\CopyFilesTask;

trait CopyFilesTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\CopyFilesTask
   */
  protected function taskMarvinCopyFiles(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\CopyFilesTask $task */
    $task = $this->task(CopyFilesTask::class);
    $task->setOptions($options);

    return $task;
  }

}

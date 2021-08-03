<?php

declare(strict_types = 1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\PickFirstFileTask;

trait PickFirstFileTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\PickFirstFileTask
   */
  protected function taskMarvinPickFirstFile(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\PickFirstFileTask $task */
    $task = $this->task(PickFirstFileTask::class);
    $task->setOptions($options);

    return $task;
  }

}

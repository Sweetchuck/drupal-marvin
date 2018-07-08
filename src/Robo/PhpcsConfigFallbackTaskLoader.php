<?php

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\PhpcsConfigFallbackTask;

trait PhpcsConfigFallbackTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\PhpcsConfigFallbackTask
   */
  protected function taskMarvinPhpcsConfigFallback(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\PhpcsConfigFallbackTask $task */
    $task = $this->task(PhpcsConfigFallbackTask::class);
    $task->setOptions($options);

    return $task;
  }

}

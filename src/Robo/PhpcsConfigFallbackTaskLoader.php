<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\PhpcsConfigFallbackTask;

trait PhpcsConfigFallbackTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\PhpcsConfigFallbackTask
   */
  protected function taskMarvinPhpcsConfigFallback(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\PhpcsConfigFallbackTask $task */
    $task = $this->task(PhpcsConfigFallbackTask::class);
    $task->setOptions($options);

    return $task;
  }

}

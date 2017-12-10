<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\ScssLintConfigFallbackTask;

trait ScssLintConfigFallbackTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\ScssLintConfigFallbackTask
   */
  protected function taskMarvinScssLintConfigFallback(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\ScssLintConfigFallbackTask $task */
    $task = $this->task(ScssLintConfigFallbackTask::class);
    $task->setOptions($options);

    return $task;
  }

}

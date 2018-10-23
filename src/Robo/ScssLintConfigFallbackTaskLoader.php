<?php

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\ScssLintConfigFallbackTask;

/**
 * @deprecated Use a NodeJS based SCSS linter and compiler instead.
 */
trait ScssLintConfigFallbackTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\ScssLintConfigFallbackTask
   */
  protected function taskMarvinScssLintConfigFallback(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\ScssLintConfigFallbackTask $task */
    $task = $this->task(ScssLintConfigFallbackTask::class);
    $task->setOptions($options);

    return $task;
  }

}

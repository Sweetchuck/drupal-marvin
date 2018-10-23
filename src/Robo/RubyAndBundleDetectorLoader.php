<?php

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\RubyAndBundleDetectorTask;

/**
 * @deprecated Use a NodeJS based SCSS linter and compiler instead.
 */
trait RubyAndBundleDetectorLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\RubyAndBundleDetectorTask
   */
  protected function taskMarvinRubyAndBundleDetector(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\RubyAndBundleDetectorTask $task */
    $task = $this->task(RubyAndBundleDetectorTask::class);
    $task->setOptions($options);

    return $task;
  }

}

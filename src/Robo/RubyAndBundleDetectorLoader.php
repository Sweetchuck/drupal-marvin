<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\RubyAndBundleDetectorTask;

trait RubyAndBundleDetectorLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\RubyAndBundleDetectorTask
   */
  protected function taskMarvinRubyAndBundleDetector(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\RubyAndBundleDetectorTask $task */
    $task = $this->task(RubyAndBundleDetectorTask::class);
    $task->setOptions($options);

    return $task;
  }

}

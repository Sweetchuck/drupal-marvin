<?php

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask;

trait VersionNumberTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask
   */
  protected function taskMarvinVersionNumberBumpExtensionInfo(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask $task */
    $task = $this->task(VersionNumberBumpExtensionInfoTask::class);
    $task->setOptions($options);

    return $task;
  }

}

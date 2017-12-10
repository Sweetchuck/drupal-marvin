<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask;

trait VersionNumberTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask
   */
  protected function taskMarvinVersionNumberBumpExtensionInfo(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask $task */
    $task = $this->task(VersionNumberBumpExtensionInfoTask::class);
    $task->setOptions($options);

    return $task;
  }

}

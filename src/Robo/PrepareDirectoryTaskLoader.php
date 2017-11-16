<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\PrepareDirectoryTask;
use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

trait PrepareDirectoryTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\PrepareDirectoryTask
   */
  protected function taskMarvinPrepareDirectory(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\PrepareDirectoryTask $task */
    $task = $this->task(PrepareDirectoryTask::class, $options);
    if ($this instanceof ContainerAwareInterface) {
      $task->setContainer($this->getContainer());
    }

    if ($this instanceof OutputAwareInterface) {
      $task->setOutput($this->output());
    }

    return $task;
  }

}

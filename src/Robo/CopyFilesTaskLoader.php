<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\CopyFilesTask;
use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

trait CopyFilesTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\CopyFilesTask
   */
  protected function taskMarvinCopyFiles(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\CopyFilesTask $task */
    $task = $this->task(CopyFilesTask::class, $options);
    if ($this instanceof ContainerAwareInterface) {
      $task->setContainer($this->getContainer());
    }

    if ($this instanceof OutputAwareInterface) {
      $task->setOutput($this->output());
    }

    return $task;
  }

}

<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\ReleaseCollectFilesTask;
use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

trait ReleaseCollectFilesTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\ReleaseCollectFilesTask
   */
  protected function taskMarvinReleaseCollectFiles(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\ReleaseCollectFilesTask $task */
    $task = $this->task(ReleaseCollectFilesTask::class, $options);
    if ($this instanceof ContainerAwareInterface) {
      $task->setContainer($this->getContainer());
    }

    if ($this instanceof OutputAwareInterface) {
      $task->setOutput($this->output());
    }

    return $task;
  }

}

<?php

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\GitCommitMsgValidatorTask;
use League\Container\ContainerAwareInterface;

trait GitCommitMsgValidatorTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\GitCommitMsgValidatorTask
   */
  protected function taskGitCommitMsgValidator(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\GitCommitMsgValidatorTask $task */
    $task = $this->task(GitCommitMsgValidatorTask::class);

    if ($this instanceof ContainerAwareInterface) {
      $container = $this->getContainer();
      if ($container) {
        $task->setContainer($this->getContainer());
      }
    }

    $task->setOptions($options);

    return $task;
  }

}

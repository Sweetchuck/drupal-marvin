<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\ManagedDrupalExtensionDeployGitHooksTask;
use Drush\marvin\Robo\Task\ManagedDrupalExtensionListTask;
use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

trait ManagedDrupalExtensionTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\ManagedDrupalExtensionListTask
   */
  protected function taskManagedDrupalExtensionList(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\ManagedDrupalExtensionListTask $task */
    $task = $this->task(ManagedDrupalExtensionListTask::class, $options);
    if ($this instanceof ContainerAwareInterface) {
      $task->setContainer($this->getContainer());
    }

    if ($this instanceof OutputAwareInterface) {
      $task->setOutput($this->output());
    }

    return $task;
  }

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\ManagedDrupalExtensionDeployGitHooksTask
   */
  protected function taskManagedDrupalExtensionDeployGitHooks(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\ManagedDrupalExtensionDeployGitHooksTask $task */
    $task = $this->task(ManagedDrupalExtensionDeployGitHooksTask::class, $options);
    if ($this instanceof ContainerAwareInterface) {
      $task->setContainer($this->getContainer());
    }

    if ($this instanceof OutputAwareInterface) {
      $task->setOutput($this->output());
    }

    return $task;
  }

}

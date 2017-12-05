<?php

namespace Drush\marvin\Robo;

use Drush\marvin\Robo\Task\ManagedDrupalExtensionDeployGitHooksTask;
use Drush\marvin\Robo\Task\ManagedDrupalExtensionListTask;

trait ManagedDrupalExtensionTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\ManagedDrupalExtensionListTask
   */
  protected function taskManagedDrupalExtensionList(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\ManagedDrupalExtensionListTask $task */
    $task = $this->task(ManagedDrupalExtensionListTask::class);
    $task->setOptions($options);

    return $task;
  }

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drush\marvin\Robo\Task\ManagedDrupalExtensionDeployGitHooksTask
   */
  protected function taskManagedDrupalExtensionDeployGitHooks(array $options = []) {
    /** @var \Drush\marvin\Robo\Task\ManagedDrupalExtensionDeployGitHooksTask $task */
    $task = $this->task(ManagedDrupalExtensionDeployGitHooksTask::class);
    $task->setOptions($options);

    return $task;
  }

}

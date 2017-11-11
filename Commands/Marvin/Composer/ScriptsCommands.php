<?php

namespace Drush\Commands\Marvin\Composer;

use Drush\Commands\Marvin\CommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;

class ScriptsCommands extends CommandsBase {

  /**
   * @command marvin:composer:post-install-cmd
   * @hidden
   */
  public function composerPostInstallCmd() {
    $cb = $this->collectionBuilder();

    $this->addTaskDeployGitHooks($cb);

    return $cb;
  }

  /**
   * @command marvin:composer:post-update-cmd
   * @hidden
   */
  public function composerPostUpdateCmd() {
    $cb = $this->collectionBuilder();

    $this->addTaskDeployGitHooks($cb);

    return $cb;
  }

  /**
   * @return $this
   */
  protected function addTaskDeployGitHooks(CollectionBuilder $cb) {
    $task = $this->getTaskDeployGitHooks();
    if ($task) {
      $cb->addTask($task);
    }

    return $this;
  }

  protected function getTaskDeployGitHooks(): ?TaskInterface {
    $composerJson = $this->composerInfo->getJson();
    if (in_array($composerJson['type'], ['project', 'drupal-project'])) {
    }

    return NULL;
  }

}

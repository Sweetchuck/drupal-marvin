<?php

namespace Drush\Commands\Marvin\Composer;

use Drush\Commands\Marvin\CommandsBase;
use Drush\marvin\Service\ManagedExtensionCollector;
use League\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;

class ScriptsCommands extends CommandsBase {

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    if (!$container->has('marvin.managed_extension_collector')) {
      $container->share(
        'marvin.managed_extension_collector',
        ManagedExtensionCollector::class
      );
    }

    return parent::setContainer($container);
  }

  /**
   * @command marvin:composer:post-install-cmd
   */
  public function composerPostInstallCmd() {
    $cb = $this->collectionBuilder();

    $this->addTaskDeployGitHooks($cb);

    return $cb;
  }

  /**
   * @command marvin:composer:post-update-cmd
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
    return NULL;
  }

}

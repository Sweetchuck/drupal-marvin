<?php

namespace Drush\Commands\Marvin\Composer;

use Drush\Commands\Marvin\CommandsBase;
use Drush\marvin\Utils;
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
    $composerJson = $this->composerInfo->getJson();
    if (!in_array($composerJson['type'], ['project', 'drupal-project'])) {
      return $this;
    }

    $managedDrupalExtensions = $this->getManagedDrupalExtensions();
    foreach ($managedDrupalExtensions as $packagePath) {
      $task = $this->getTaskDeployGitHooks($packagePath);
      if ($task) {
        $cb->addTask($task);
      }
    }

    return $this;
  }

  protected function getTaskDeployGitHooks(string $packagePath): ?TaskInterface {
    $config = $this->getConfig();
    $marvinRootDir = Utils::marvinRootDir();

    return $this
      ->taskManagedDrupalExtensionDeployGitHooks()
      ->setRootProjectDir($config->get('env.cwd'))
      ->setComposerExecutable($config->get('command.marvin.settings.composerExecutable'))
      ->setPackagePath($packagePath)
      ->setHookFilesSourceDir("$marvinRootDir/gitHooks/self")
      ->setCommonTemplateFileName("$marvinRootDir/gitHooks/managedExtension/_common.php");
  }

}

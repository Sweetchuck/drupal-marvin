<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin;

use Robo\Collection\CollectionBuilder;

class ComposerCommandsBase extends CommandsBase {

  protected static string $classKeyPrefix = 'marvin.composer';

  protected string $customEventNamePrefix = 'marvin:composer';

  /**
   * @return \Robo\Collection\CollectionBuilder|\Robo\Task\Composer\Validate
   */
  protected function getTaskComposerValidate(string $packagePath): CollectionBuilder {
    // @todo Relative or absolute path to the composer executable.
    return $this
      ->taskComposerValidate($this->getComposerExecutable())
      ->dir($packagePath);
  }

  /**
   * @return \Robo\Collection\CollectionBuilder|\Sweetchuck\Robo\Composer\Task\RemoveIndirectDependenciesTask
   */
  protected function getTaskComposerRemoveIndirectDependencies(string $packagePath): CollectionBuilder {
    return $this
      ->taskComposerRemoveIndirectDependencies()
      ->setWorkingDirectory($packagePath);
  }

  protected function getComposerExecutable(): string {
    return $this
      ->getConfig()
      ->get('marvin.composerExecutable', 'composer');
  }

}

<?php

namespace Drush\Commands\marvin\Lint;

use Drush\Commands\marvin\CommandsBase as MarvinCommandsBase;
use Robo\Collection\CollectionBuilder;

class ComposerValidateCommandsBase extends MarvinCommandsBase {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Robo\Task\Composer\Validate
   */
  protected function getTaskComposerValidate(string $packagePath): CollectionBuilder {
    // @todo Relative or absolute path to the composer executable.
    return $this
      ->taskComposerValidate($this->getComposerExecutable())
      ->dir($packagePath);
  }

  protected function getComposerExecutable(): string {
    return $this
      ->getConfig()
      ->get('command.marvin.settings.composerExecutable', 'composer');
  }

}

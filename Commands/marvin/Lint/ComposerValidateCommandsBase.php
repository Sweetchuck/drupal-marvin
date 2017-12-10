<?php

namespace Drush\Commands\marvin\Lint;

use Drush\Commands\marvin\CommandsBase as MarvinCommandsBase;
use Robo\Contract\TaskInterface;

class ComposerValidateCommandsBase extends MarvinCommandsBase {

  protected function getTaskComposerValidate(string $packagePath): TaskInterface {
    $composerExecutable = $this->getConfig()->get('command.marvin.settings.composerExecutable', 'composer');

    // @todo Relative or absolute path to the composer executable.
    return $this
      ->taskComposerValidate($composerExecutable)
      ->dir($packagePath);
  }

}

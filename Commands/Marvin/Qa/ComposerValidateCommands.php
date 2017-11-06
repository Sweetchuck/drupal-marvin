<?php

namespace Drush\Commands\Marvin\Qa;

use Drush\Commands\Marvin\CommandsBase;
use Robo\Contract\TaskInterface;

class ComposerValidateCommands extends CommandsBase {

  /**
   * @command marvin:qa:composer:validate
   * @bootstrap none
   */
  public function composerValidate(): TaskInterface {
    return $this->taskComposerValidate();
  }

}

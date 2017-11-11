<?php

namespace Drush\Commands\Marvin\GitHook;

use Drush\Commands\Marvin\GitHookCommandsBase;

class PreCommitCommands extends GitHookCommandsBase {

  /**
   * @command marvin:git-hook:pre-commit
   * @hidden
   */
  public function preCommit(string $packagePath = '') {
    // @todo Make it configurable what to run.
    return $this
      ->collectionBuilder()
      ->addCode($this->invokeCommand('marvin:qa:composer:validate'))
      ->addCode($this->invokeCommand('marvin:qa:lint:phpcs'))
      ->addCode($this->invokeCommand('marvin:qa:phpunit'));
  }

}

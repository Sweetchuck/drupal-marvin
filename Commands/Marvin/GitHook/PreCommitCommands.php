<?php

namespace Drush\Commands\Marvin\GitHook;

use Drush\Commands\Marvin\GitHookCommandsBase;

class PreCommitCommands extends GitHookCommandsBase {

  /**
   * @command marvin:git-hook:pre-commit
   */
  public function preCommit() {
    return $this
      ->collectionBuilder()
      ->addCode($this->invokeCommand('marvin:qa:composer:validate'))
      ->addCode($this->invokeCommand('marvin:qa:lint:phpcs'));
  }

}

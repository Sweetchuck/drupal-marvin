<?php

namespace Drush\Commands\Marvin\GitHook;

use Drush\Commands\Marvin\GitHookCommandsBase;

class PreCommitCommands extends GitHookCommandsBase {

  /**
   * @command marvin:git-hook:pre-commit
   * @hidden
   */
  public function preCommit(string $packagePath = '') {
    $arguments = [];
    if ($packagePath) {
      $arguments[] = $packagePath;
    }

    // @todo Make it configurable what to run.
    return $this
      ->collectionBuilder()
      ->addCode($this->invokeCommand('marvin:qa:composer:validate', $arguments))
      ->addCode($this->invokeCommand('marvin:qa:lint:phpcs', $arguments))
      ->addCode($this->invokeCommand('marvin:qa:phpunit', $arguments));
  }

}

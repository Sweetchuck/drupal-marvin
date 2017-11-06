<?php

namespace Drush\Commands\Marvin\GitHook;

use Drush\Commands\Marvin\GitHookCommandsBase;

class PreCommitCommands extends GitHookCommandsBase {

  /**
   * @command marvin:git-hook:pre-commit
   */
  public function preCommit() {
    $gitHook = $this->getConfig()->get('marvin.settings.gitHook');
    $this->yell($gitHook);

    throw new \Exception('My Dummy Exception');
  }

}

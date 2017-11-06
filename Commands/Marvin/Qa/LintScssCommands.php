<?php

namespace Drush\Commands\Marvin\Qa;

class LintScssCommands extends LintCommandsBase {

  /**
   * @command marvin:qa:lint:scss
   * @bootstrap none
   * @option string $git-hook
   *   Name of the Git hook where this command trigger from. eg: pre-commit.
   */
  public function lintScss() {
    $this->say('@todo Implement ' . __METHOD__);
  }

}

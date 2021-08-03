<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin;

use Robo\Collection\CollectionBuilder;

class ComposerScriptsCommands extends CommandsBase {

  protected static string $classKeyPrefix = 'marvin.composerScripts';

  protected string $customEventNamePrefix = 'marvin:composer-scripts';

  /**
   * Do something on Composer post-install-cmd event.
   *
   * @command marvin:composer:post-install-cmd
   * @bootstrap none
   * @hidden
   */
  public function composerPostInstallCmd(
    array $options = [
      'dev-mode' => FALSE,
    ]
  ): CollectionBuilder {
    return $this->delegate('post-install-cmd', getcwd());
  }

  /**
   * Do something on Composer post-update-cmd event.
   *
   * @command marvin:composer:post-update-cmd
   * @bootstrap none
   * @hidden
   */
  public function composerPostUpdateCmd(
    array $options = [
      'dev-mode' => FALSE,
    ]
  ): CollectionBuilder {
    return $this->delegate('post-update-cmd', getcwd());
  }

}

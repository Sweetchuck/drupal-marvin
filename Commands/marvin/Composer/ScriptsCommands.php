<?php

namespace Drush\Commands\marvin\Composer;

use Drush\Commands\marvin\CommandsBase;

class ScriptsCommands extends CommandsBase {

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':composer';
  }

  /**
   * @command marvin:composer:post-install-cmd
   * @hidden
   */
  public function composerPostInstallCmd() {
    return $this->delegate('post-install-cmd');
  }

  /**
   * @command marvin:composer:post-update-cmd
   * @hidden
   */
  public function composerPostUpdateCmd() {
    return $this->delegate('post-update-cmd');
  }

}

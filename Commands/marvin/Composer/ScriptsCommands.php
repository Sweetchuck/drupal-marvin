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
   * Do something on Composer post-install-cmd event.
   *
   * @command marvin:composer:post-install-cmd
   * @hidden
   */
  public function composerPostInstallCmd(
    array $options = [
      'dev-mode' => FALSE,
    ]
  ) {
    return $this->delegate('post-install-cmd');
  }

  /**
   * Do something on Composer post-update-cmd event.
   *
   * @command marvin:composer:post-update-cmd
   * @hidden
   */
  public function composerPostUpdateCmd(
    array $options = [
      'dev-mode' => FALSE,
    ]
  ) {
    return $this->delegate('post-update-cmd');
  }

}

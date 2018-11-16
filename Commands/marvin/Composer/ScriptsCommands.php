<?php

namespace Drush\Commands\marvin\Composer;

use Drush\Commands\marvin\CommandsBase;
use Robo\Collection\CollectionBuilder;

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

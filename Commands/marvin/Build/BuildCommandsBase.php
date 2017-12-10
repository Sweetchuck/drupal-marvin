<?php

namespace Drush\Commands\marvin\Build;

use Drush\Commands\marvin\CommandsBase;

class BuildCommandsBase extends CommandsBase {

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':build';
  }

}

<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drush\Commands\marvin\PhpcsCommandsBase;
use Robo\Collection\CollectionBuilder;

class PhpcsCommands extends PhpcsCommandsBase {

  /**
   * @command marvin:lint:phpcs
   */
  public function marvinLintPhpcs(string $workingDirectory): CollectionBuilder {
    return $this->getTaskLintPhpcsExtension($workingDirectory);
  }

}

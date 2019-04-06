<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drush\Commands\marvin\NpmCommandsBase;
use Robo\Collection\CollectionBuilder;

class NpmCommands extends NpmCommandsBase {

  /**
   * @command marvin:npm:install
   */
  public function npmInstall(string $packagePath): CollectionBuilder {
    return $this->getTaskNpmInstallPackage('', $packagePath);
  }

}

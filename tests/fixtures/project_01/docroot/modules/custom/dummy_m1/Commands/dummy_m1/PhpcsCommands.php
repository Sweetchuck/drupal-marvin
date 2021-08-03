<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drush\Commands\marvin\PhpcsCommandsBase;
use League\Container\ContainerInterface as LeagueContainer;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\LintReport\Reporter\BaseReporter;

class PhpcsCommands extends PhpcsCommandsBase {

  /**
   * @hook pre-command @initLintReporters
   */
  public function initLintReporters() {
    $lintServices = BaseReporter::getServices();
    $container = $this->getContainer();
    foreach ($lintServices as $name => $class) {
      if ($container->has($name)) {
        continue;
      }

      if ($container instanceof LeagueContainer) {
        $container->share($name, $class);
      }
    }
  }

  /**
   * @command marvin:lint:phpcs
   *
   * @initLintReporters
   */
  public function marvinLintPhpcs(string $workingDirectory): CollectionBuilder {
    return $this->getTaskLintPhpcsExtension($workingDirectory);
  }

}

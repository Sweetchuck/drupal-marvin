<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drupal\marvin\Utils;
use Drush\Commands\marvin\PhpcsCommandsBase;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\LintReport\Reporter\BaseReporter;

class PhpcsCommands extends PhpcsCommandsBase {

  /**
   * @hook pre-command @initLintReporters
   */
  public function initLintReporters() {
    $lintServices = BaseReporter::getServices();
    $container = $this->getContainer();
    foreach ($lintServices as $id => $class) {
      Utils::addDefinitionsToContainer(
        [
          $id => [
            'shared' => FALSE,
            'class' => $class,
          ],
        ],
        $container,
      );
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

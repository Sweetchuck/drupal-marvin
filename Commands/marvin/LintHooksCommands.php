<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin;

use Drupal\marvin\Utils;
use Sweetchuck\LintReport\Reporter\BaseReporter;

class LintHooksCommands extends CommandsBase {

  /**
   * @hook pre-command @marvinInitLintReporters
   */
  public function initLintReporters() {
    $this->getLogger()->debug('triggered hook: pre-command @marvinInitLintReporters');

    Utils::initLintReporters(
      BaseReporter::getServices(),
      $this->getContainer(),
    );
  }

}

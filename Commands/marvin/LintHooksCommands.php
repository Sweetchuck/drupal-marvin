<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\Attributes\PreCommandInitLintReporters as PreCommandMarvinInitLintReporters;
use Drush\Attributes\Hook as CliHook;
use League\Container\Container as LeagueContainer;
use Sweetchuck\LintReport\Reporter\BaseReporter;

class LintHooksCommands extends CommandsBase {

  #[CliHook(
    type: HookManager::PRE_COMMAND_HOOK,
    selector: PreCommandMarvinInitLintReporters::SELECTOR,
  )]
  public function onHookPreCommandInitLintReporters(): void {
    $lintServices = BaseReporter::getServices();
    $container = $this->getContainer();
    if (!($container instanceof LeagueContainer)) {
      return;
    }

    foreach ($lintServices as $name => $class) {
      if ($container->has($name)) {
        continue;
      }

      $container
        ->add($name, $class)
        ->setShared(FALSE);
    }
  }

}

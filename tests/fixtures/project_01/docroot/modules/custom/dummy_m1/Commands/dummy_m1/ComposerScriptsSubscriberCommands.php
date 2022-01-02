<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drush\Commands\marvin\CommandsBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerScriptsSubscriberCommands extends CommandsBase {

  /**
   * @hook on-event marvin:composer-scripts:post-install-cmd
   */
  public function onEventMarvinComposerScriptsPostInstallCmd(InputInterface $input, OutputInterface $output): array {
    $method = __FUNCTION__;

    return [
      'dummy_m1' => [
        'task' => function () use ($output, $method) {
          $class = $this->getClassName();

          $output->writeln("$class::$method called");
        },
      ],
    ];
  }

  /**
   * @hook on-event marvin:composer-scripts:post-update-cmd
   */
  public function onEventMarvinComposerScriptsPostUpdateCmd(InputInterface $input, OutputInterface $output): array {
    $method = __FUNCTION__;

    return [
      'dummy_m1' => [
        'task' => function () use ($output, $method) {
          $class = $this->getClassName();

          $output->writeln("$class::$method called");
        },
      ],
    ];
  }

  protected function getClassName(): string {
    $parts = explode('\\', get_called_class());

    return end($parts);
  }

}

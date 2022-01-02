<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drush\Commands\marvin\CommandsBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitHookSubscriberCommands extends CommandsBase {

  /**
   * @hook on-event marvin:git-hook:pre-commit
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input, OutputInterface $output): array {
    return [
      'dummy_m1' => [
        'weight' => -200,
        'task' => function () use ($output) {
          $output->write('GitHookSubscriberCommands::onEventMarvinGitHookPreCommit called');
        },
      ],
    ];
  }

}

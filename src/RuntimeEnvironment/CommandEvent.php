<?php

declare(strict_types=1);

namespace Drupal\marvin\RuntimeEnvironment;

use Drupal\marvin\CommandEvent as BaseCommandEvent;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandEvent extends BaseCommandEvent {

  public const string EVENT_SWITCH_TASKS_COLLECT = 'marvin.runtime_environment.switch.tasks.collect';

  public const string EVENT_SWITCH_TASKS_ALTER = 'marvin.runtime_environment.switch.tasks.alter';

  /**
   * @phpstan-param array<string, mixed> $rte
   * @phpstan-param array<string, marvin-task-definition> $taskDefinitions
   */
  public function __construct(
    public readonly InputInterface $input,
    public readonly OutputInterface $output,
    public readonly ?string $gitHookName,
    public readonly CollectionBuilder $collectionBuilder,
    public array $taskDefinitions,
    public readonly array $rte,
  ) {
  }

}

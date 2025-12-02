<?php

declare(strict_types=1);

namespace Drupal\marvin\Artifact;

use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BuildActionEvent extends Event {

  public const string EVENT_TASKS_COLLECT = 'marvin.artifact.build.all.tasks.collect';

  public const string EVENT_TASKS_ALTER = 'marvin.artifact.build.all.tasks.alter';

  /**
   * @phpstan-param array<marvin-artifact-type> $artifactType
   * @phpstan-param \Robo\Collection\CollectionBuilder $collectionBuilder
   *   Only for "new \Robo\Collection\CallableTask()" as $reference.
   *   However, callable|\Closure also can be used.
   * @phpstan-param array<marvin-task-definition> $taskDefinitions
   */
  public function __construct(
    public readonly InputInterface $input,
    public readonly OutputInterface $output,
    public readonly array $artifactType,
    public readonly string $srcDir,
    public readonly CollectionBuilder $collectionBuilder,
    public array $taskDefinitions,
  ) {
  }

}

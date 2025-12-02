<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CommandEvent extends Event {

  /**
   * @phpstan-param array<string, mixed> $taskDefinitions
   *
   * @todo Replace ::$gitHookName with ::$context array<string, mixed>.
   */
  public function __construct(
    public readonly InputInterface $input,
    public readonly OutputInterface $output,
    public readonly ?string $gitHookName,
    public readonly CollectionBuilder $collectionBuilder,
    public array $taskDefinitions,
  ) {
  }

}

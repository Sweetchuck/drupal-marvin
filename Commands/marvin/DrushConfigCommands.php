<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandResult;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;

class DrushConfigCommands extends CommandsBase {

  protected static string $classKeyPrefix = 'marvin.drushConfig';

  protected string $customEventNamePrefix = 'marvin:drush-config';

  /**
   * Prints out the current Drush configuration.
   *
   * @phpstan-param array<string, mixed> $options
   */
  #[CLI\Command(name: 'marvin:drush-config')]
  #[CLI\Bootstrap(level: DrupalBootLevels::MAX)]
  #[CLI\Option(
    name: 'format',
    description: 'Output format',
  )]
  public function cmdMarvinDrushConfigExecute(
    array $options = [
      'format' => 'yaml',
    ]
  ): CommandResult {
    return CommandResult::data($this->getConfig()->export());
  }

}

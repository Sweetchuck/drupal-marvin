<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin;

class DrushConfigCommands extends CommandsBase {

  protected static string $classKeyPrefix = 'marvin.drushConfig';

  protected string $customEventNamePrefix = 'marvin:drush-config';

  /**
   * Prints out the current Drush configuration.
   *
   * @command marvin:drush-config
   * @bootstrap max
   *
   * @option string $format
   *   Output format.
   */
  public function drushConfig(
    array $options = [
      'format' => 'yaml',
    ]
  ): array {
    return $this->getConfig()->export();
  }

}

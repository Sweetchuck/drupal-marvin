<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drush\Commands\DrushCommands;

class MarvinArtifactCommands extends DrushCommands {

  /**
   * @hook on-event marvin:artifact:types
   */
  public function onEventMarvinArtifactTypes(string $projectType): array {
    $types = [];

    if ($projectType === 'integrationTest') {
      $types['dummy'] = [
        'label' => dt('Dummy - @projectType', ['@projectType' => $projectType]),
        'description' => dt('Do not use it'),
      ];
    }

    return $types;
  }

}

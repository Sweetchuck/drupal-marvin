<?php

namespace Drush\Commands\Marvin;

use Robo\Contract\OutputAwareInterface;

class ManagedDrupalExtensionCommands extends CommandsBase implements OutputAwareInterface {

  /**
   * @command marvin:managed-drupal-extension:list
   * @bootstrap none
   */
  public function managedDrupalExtensionList(
    array $options = [
      'format' => 'yaml',
    ]
  ): array {
    return $this->getManagedDrupalExtensions();
  }

}

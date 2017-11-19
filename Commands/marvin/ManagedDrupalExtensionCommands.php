<?php

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\AnnotationData;
use Robo\Contract\OutputAwareInterface;
use Symfony\Component\Console\Command\Command;

class ManagedDrupalExtensionCommands extends CommandsBase implements OutputAwareInterface {

  /**
   * @hook option marvin:managed-drupal-extension:list
   */
  public function managedDrupalExtensionListHookOption(Command $command, AnnotationData $annotationData) {
    // @todo Find another way, because this one does not work.
    $command->setHidden(!$this->isIncubatorProject());
  }

  /**
   * List the managed Drupal extensions.
   *
   * @command marvin:managed-drupal-extension:list
   * @bootstrap none
   * @option $format
   */
  public function managedDrupalExtensionList(
    array $options = [
      'format' => 'yaml',
    ]
  ): array {
    return $this->getManagedDrupalExtensions();
  }

}

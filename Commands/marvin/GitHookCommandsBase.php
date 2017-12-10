<?php

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\GitHooksStdInputReader\ReaderFactory;

class GitHookCommandsBase extends CommandsBase implements CustomEventAwareInterface {

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return 'marvin:git-hook';
  }

  /**
   * {@inheritdoc}
   */
  protected function delegatePrepareCollectionBuilder(CollectionBuilder $cb, string $eventBaseName, array $args) {
    $cb
      ->getState()
      ->offsetSet('gitHooks.stdInputReader', ReaderFactory::createInstance($eventBaseName, STDIN));

    return $this;
  }

}

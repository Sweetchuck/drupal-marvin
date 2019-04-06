<?php

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\GitHooksStdInputReader\ReaderFactory;

/**
 * Class GitHookCommandsBase.
 *
 * @package Drush\Commands\marvin
 */
class GitHookCommandsBase extends CommandsBase implements CustomEventAwareInterface {

  /**
   * {@inheritdoc}
   */
  protected static $classKeyPrefix = 'marvin.gitHook';

  /**
   * {@inheritdoc}
   */
  protected $customEventNamePrefix = 'marvin:git-hook';

  /**
   * {@inheritdoc}
   */
  protected function delegatePrepareCollectionBuilder(CollectionBuilder $cb, string $eventBaseName, array $args) {
    $cb
      ->getState()
      ->offsetSet(
        'gitHooks.stdInputReader',
        ReaderFactory::createInstance($eventBaseName, $this->getStdInputFileHandler())
      );

    return $this;
  }

  /**
   * @return resource
   */
  protected function getStdInputFileHandler() {
    return STDIN;
  }

}

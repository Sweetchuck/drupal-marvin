<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Traits;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigAwareTrait;
use Drupal\marvin\Robo\ArtifactFilesTaskTrait;
use Drupal\marvin\Robo\FileSystemTaskTrait;
use Drupal\marvin\Robo\NodeJsTaskTrait;
use Drupal\marvin\Robo\VersionNumberTaskTrait;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Common\OutputAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;
use Robo\TaskAccessor;

class TaskBuilder implements
  BuilderAwareInterface,
  ConfigAwareInterface,
  ContainerAwareInterface,
  LoggerAwareInterface,
  OutputAwareInterface,
  StateAwareInterface {

  use TaskAccessor;
  use ConfigAwareTrait;
  use ContainerAwareTrait;
  use LoggerAwareTrait;
  use OutputAwareTrait;
  use StateAwareTrait;

  use ArtifactFilesTaskTrait {
    taskMarvinArtifactCollectFilesToCopy as public;
  }

  use FileSystemTaskTrait {
    taskMarvinArtifactCopyFiles as public;
  }

  use NodeJsTaskTrait {
    taskMarvinDetectNodeJs as public;
  }

  use VersionNumberTaskTrait {
    taskMarvinUpdateExtensionVersionNumber as public;
  }

  public function getLogger(): LoggerInterface {
    return $this->logger;
  }

  public function collectionBuilder(): CollectionBuilder {
    /** @phpstan-ignore-next-line */
    return CollectionBuilder::create($this->getContainer(), NULL);
  }

}

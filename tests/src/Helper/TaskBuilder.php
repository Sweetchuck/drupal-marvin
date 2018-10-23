<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Helper;

use Drupal\marvin\Robo\CopyFilesTaskLoader;
use Drupal\marvin\Robo\PhpcsConfigFallbackTaskLoader;
use Drupal\marvin\Robo\PrepareDirectoryTaskLoader;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\TaskIO;
use Robo\Contract\BuilderAwareInterface;
use Robo\State\StateAwareTrait;
use Robo\TaskAccessor;

class TaskBuilder implements BuilderAwareInterface, ContainerAwareInterface {

  use TaskAccessor;
  use ContainerAwareTrait;
  use StateAwareTrait;
  use TaskIO;

  use CopyFilesTaskLoader {
    taskMarvinCopyFiles as public;
  }

  use PhpcsConfigFallbackTaskLoader {
    taskMarvinPhpcsConfigFallback as public;
  }

  use PrepareDirectoryTaskLoader {
    taskMarvinPrepareDirectory as public;
  }

  public function collectionBuilder(): CollectionBuilder {
    return CollectionBuilder::create($this->getContainer(), NULL);
  }

}

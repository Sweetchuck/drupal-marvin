<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Helper;

use Drupal\marvin\Robo\ArtifactCollectFilesTaskLoader;
use Drupal\marvin\Robo\CopyFilesTaskLoader;
use Drupal\marvin\Robo\GitCommitMsgValidatorTaskLoader;
use Drupal\marvin\Robo\NodeDetectorTaskLoader;
use Drupal\marvin\Robo\PhpcsConfigFallbackTaskLoader;
use Drupal\marvin\Robo\PrepareDirectoryTaskLoader;
use Drupal\marvin\Robo\VersionNumberTaskLoader;
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

  use ArtifactCollectFilesTaskLoader {
    taskMarvinArtifactCollectFiles as public;
  }

  use CopyFilesTaskLoader {
    taskMarvinCopyFiles as public;
  }

  use GitCommitMsgValidatorTaskLoader {
    taskMarvinGitCommitMsgValidator as public;
  }

  use NodeDetectorTaskLoader {
    taskMarvinNodeDetector as public;
  }

  use PhpcsConfigFallbackTaskLoader {
    taskMarvinPhpcsConfigFallback as public;
  }

  use PrepareDirectoryTaskLoader {
    taskMarvinPrepareDirectory as public;
  }

  use VersionNumberTaskLoader {
    taskMarvinVersionNumberBumpExtensionInfo as public;
  }

  public function collectionBuilder(): CollectionBuilder {
    return CollectionBuilder::create($this->getContainer(), NULL);
  }

}

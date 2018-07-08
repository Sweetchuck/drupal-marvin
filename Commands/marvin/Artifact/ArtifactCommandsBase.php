<?php

namespace Drush\Commands\marvin\Artifact;

use Drush\Commands\marvin\CommandsBase;
use Drupal\marvin\Robo\CopyFilesTaskLoader;
use Drupal\marvin\Robo\PrepareDirectoryTaskLoader;
use Drupal\marvin\Robo\ArtifactCollectFilesTaskLoader;

class ArtifactCommandsBase extends CommandsBase {

  use ArtifactCollectFilesTaskLoader;
  use CopyFilesTaskLoader;
  use PrepareDirectoryTaskLoader;

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':artifact';
  }

}

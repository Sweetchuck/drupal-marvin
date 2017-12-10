<?php

namespace Drush\Commands\marvin\Artifact;

use Drush\Commands\marvin\CommandsBase;
use Drush\marvin\Robo\CopyFilesTaskLoader;
use Drush\marvin\Robo\PrepareDirectoryTaskLoader;
use Drush\marvin\Robo\ArtifactCollectFilesTaskLoader;

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

<?php

namespace Drush\Commands\marvin;

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
  protected static $classKeyPrefix = 'marvin.artifact';

  /**
   * {@inheritdoc}
   */
  protected $customEventNamePrefix = 'marvin:artifact';

}

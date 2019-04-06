<?php

namespace Drush\Commands\marvin;

class ArtifactBuildCommandsBase extends ArtifactCommandsBase {

  /**
   * {@inheritdoc}
   */
  protected $customEventNamePrefix = 'marvin:artifact:build';

}

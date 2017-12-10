<?php

namespace Drush\Commands\marvin\Artifact;

class ArtifactBuildCommandsBase extends ArtifactCommandsBase {

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':build';
  }

}

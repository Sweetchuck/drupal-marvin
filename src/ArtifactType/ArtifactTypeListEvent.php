<?php

declare(strict_types=1);

namespace Drupal\marvin\ArtifactType;

use Symfony\Contracts\EventDispatcher\Event;

class ArtifactTypeListEvent extends Event {

  public const string EVENT_COLLECT = 'marvin.artifactType.list.collect';

  public const string EVENT_ALTER = 'marvin.artifactType.list.alter';

  public function __construct(
    public readonly ArtifactTypeList $artifactTypeList,
  ) {
  }

}

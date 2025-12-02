<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\UpdateExtensionVersionNumberTask;

/**
 * @phpstan-ignore-next-line trait.unused
 */
trait VersionNumberTaskTrait {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\UpdateExtensionVersionNumberTask
   *
   * @phpstan-param marvin-robo-task-version-number-bump-extension-info-options $options
   */
  protected function taskMarvinUpdateExtensionVersionNumber(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\UpdateExtensionVersionNumberTask $task */
    $task = $this->task(UpdateExtensionVersionNumberTask::class);
    $task->setOptions($options);

    return $task;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\Robo\Task\DetectNodeJsTask;
use League\Container\ContainerAwareInterface;

trait NodeJsTaskTrait {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\DetectNodeJsTask
   *
   * @phpstan-param marvin-robo-task-node-detector-options $options
   */
  protected function taskMarvinDetectNodeJs(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\DetectNodeJsTask $task */
    $task = $this->task(DetectNodeJsTask::class);

    if ($this instanceof ContainerAwareInterface) {
      $container = $this->getContainer();
      if ($container) {
        $task->setContainer($this->getContainer());
      }
    }

    $task->setOptions($options);

    return $task;
  }

}

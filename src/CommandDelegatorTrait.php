<?php

namespace Drupal\marvin;

use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;

trait CommandDelegatorTrait {

  protected function getCustomEventNamePrefix(): string {
    return 'marvin';
  }

  protected function getCustomEventName(string $eventBaseName): string {
    return $this->getCustomEventNamePrefix() . ($eventBaseName ? ":{$eventBaseName}" : '');
  }

  /**
   * @todo Find a better name for this method.
   * @todo Make this method universal.
   */
  protected function delegate(string $eventBaseName, ...$args): CollectionBuilder {
    $cb = $this->collectionBuilder();

    $this
      ->delegatePrepareCollectionBuilder($cb, $eventBaseName, $args)
      ->delegateAddTasks($cb, $this->delegateCollectTaskDefinitions($eventBaseName, $args));

    return $cb;
  }

  protected function delegateCollectTaskDefinitions(string $eventBaseName, array $args): array {
    $taskDefinitions = [];
    /** @var callable[] $eventHandlers */
    $eventHandlers = $this->getCustomEventHandlers($this->getCustomEventName($eventBaseName));
    foreach ($eventHandlers as $eventHandler) {
      $taskDefinitions += $eventHandler($this->input(), $this->output(), ...$args);
    }
    uasort($taskDefinitions, new ArrayValueComparer(['weight' => 0]));

    return $taskDefinitions;
  }

  protected function delegatePrepareCollectionBuilder(CollectionBuilder $cb, string $eventBaseName, array $args) {
    return $this;
  }

  /**
   * @return $this
   */
  protected function delegateAddTasks(CollectionBuilder $cb, array $taskDefinitions) {
    $taskTypes = ['task', 'rollback', 'completion'];
    foreach ($taskDefinitions as $taskDefinition) {
      foreach ($taskTypes as $taskType) {
        if (isset($taskDefinition[$taskType])) {
          $this->delegateAddTask($cb, $taskDefinition[$taskType], $taskType);
        }
      }
    }

    return $this;
  }

  /**
   * @param \Robo\Collection\CollectionBuilder $cb
   * @param \Robo\Contract\TaskInterface|\Closure $task
   * @param string $taskType
   *
   * @return $this
   */
  protected function delegateAddTask(CollectionBuilder $cb, $task, string $taskType) {
    if ($taskType === 'rollback' || $taskType === 'completion') {
      $method = $task instanceof TaskInterface ? $taskType : "{$taskType}Code";
      $cb->$method($task);

      return $this;
    }

    if ($task instanceof TaskInterface) {
      $cb->addTask($task);

      return $this;
    }

    $cb->addCode($task);

    return $this;
  }

}

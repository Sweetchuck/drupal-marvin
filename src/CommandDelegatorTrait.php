<?php

namespace Drupal\marvin;

use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @property null|\Psr\Log\LoggerInterface $logger
 */
trait CommandDelegatorTrait {

  /**
   * @var string
   */
  protected $customEventNamePrefix = 'marvin';

  protected function getCustomEventNamePrefix(): string {
    return $this->customEventNamePrefix;
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

    $taskDefinitions = $this->delegateCollectTaskDefinitions($eventBaseName, $args);
    $this
      ->delegateLogTaskDefinitions($eventBaseName, $taskDefinitions)
      ->delegatePrepareCollectionBuilder($cb, $eventBaseName, $args)
      ->delegateAddTasks($cb, $taskDefinitions);

    return $cb;
  }

  protected function delegateCollectTaskDefinitions(string $eventBaseName, array $args): array {
    $eventName = $this->getCustomEventName($eventBaseName);
    if (!empty($this->logger)) {
      $this->logger->debug(
        'Collecting task definitions for event "<info>{eventName}</info>"',
        ['eventName' => $eventName]
      );
    }

    $taskDefinitions = [];
    /** @var callable[] $eventHandlers */
    $eventHandlers = $this->getCustomEventHandlers($eventName);
    foreach ($eventHandlers as $eventHandler) {
      $provider = Utils::callableToString($eventHandler);
      $tasks = $eventHandler($this->input(), $this->output(), ...$args);
      foreach ($tasks as &$task) {
        $task += [
          'provider' => $provider,
          'weight' => 0,
          'description' => '',
        ];
      }
      $taskDefinitions += $tasks;
    }
    uasort($taskDefinitions, new ArrayValueComparer(['weight' => 0]));

    return $taskDefinitions;
  }

  /**
   * @return $this
   *
   * @SuppressWarnings(UnusedFormalParameter)
   */
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

  protected function delegateLogTaskDefinitions(string $eventBaseName, iterable $taskDefinitions) {
    $output = new BufferedOutput();
    $table = Utils::taskDefinitionsAsTable($taskDefinitions, $output);
    $table->render();
    $this->getLogger()->debug(
      "tasks to run on event: {eventName}\n{taskDefinitionsTable}",
      [
        'eventName' => $this->getCustomEventName($eventBaseName),
        'taskDefinitionsTable' => $output->fetch(),
      ]
    );

    return $this;
  }

}

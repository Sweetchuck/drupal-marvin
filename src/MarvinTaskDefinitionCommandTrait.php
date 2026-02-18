<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Psr\Log\LoggerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Marvin task definition helper methods.
 *
 * This can be added to #[AsCommand] classes.
 *
 * @property \Drupal\marvin\Utils $utils
 * @property \Psr\Log\LoggerInterface $logger
 */
trait MarvinTaskDefinitionCommandTrait {

  /**
   * @param ?\Robo\Symfony\ConsoleIO $io
   *
   * @return \Robo\Collection\CollectionBuilder
   */
  abstract protected function collectionBuilder($io = NULL);

  /**
   * @phpstan-param array<string, marvin-task-definition> $taskDefinitions
   */
  protected function mtdRun(string $pipelineId, CollectionBuilder $cb, array $taskDefinitions): int {
    $this
      ->mtdrPopulateDefaultValues($taskDefinitions)
      ->mtdrSort($taskDefinitions)
      ->mtdrAddTasks($cb, $taskDefinitions)
      ->mtdrLogTaskDefinitions($this->logger, $pipelineId, $taskDefinitions);

    try {
      $result = $cb->run();
      if (!$result->wasSuccessful()) {
        $this->logger->error($result->getMessage());

        return Command::FAILURE;
      }

      return Command::SUCCESS;
    }
    catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());

      return Command::FAILURE;
    }
  }

  /**
   * @phpstan-param array<string, marvin-task-definition> $taskDefinitions
   */
  protected function mtdrPopulateDefaultValues(array &$taskDefinitions): static {
    foreach ($taskDefinitions as $id => &$taskDefinition) {
      $idParts = explode('.', $id);
      $provider = array_pop($idParts);
      $name = implode('.', $idParts);

      $taskDefinition['id'] = $id;
      $taskDefinition += [
        'weight' => 0,
        'name' => $name,
        'provider' => $provider,
        'description' => '',
      ];
    }

    return $this;
  }

  /**
   * @phpstan-param array<string, marvin-task-definition> $taskDefinitions
   */
  protected function mtdrSort(array &$taskDefinitions): static {
    $comparer = new ArrayValueComparer();
    $comparer->setKeys([
      'weight' => [
        'default' => 0,
      ],
    ]);
    uasort($taskDefinitions, $comparer);

    return $this;
  }

  /**
   * @phpstan-param array<string, marvin-task-definition> $taskDefinitions
   */
  protected function mtdrAddTasks(CollectionBuilder $cb, array $taskDefinitions): static {
    $taskTypes = ['task', 'rollback', 'completion'];
    foreach ($taskDefinitions as $taskDefinition) {
      foreach ($taskTypes as $taskType) {
        if (isset($taskDefinition[$taskType])) {
          $this->mtdrAddTask($cb, $taskDefinition[$taskType], $taskType);
        }
      }
    }

    return $this;
  }

  protected function mtdrAddTask(
    CollectionBuilder $cb,
    callable|TaskInterface $task,
    string $taskType,
  ): static {
    $isCallable = !($task instanceof TaskInterface);
    if ($isCallable) {
      switch ($taskType) {
        case 'task':
          $cb->addCode($task);
          break;

        case 'rollback':
          $cb->rollbackCode($task);
          break;

        case 'completion':
          $cb->completionCode($task);
          break;
      }

      return $this;
    }

    switch ($taskType) {
      case 'task':
        $cb->addTask($task);
        break;

      case 'rollback':
        $cb->rollback($task);
        break;

      case 'completion':
        $cb->completion($task);
        break;
    }

    return $this;
  }

  /**
   * @phpstan-param array<string, marvin-task-definition> $taskDefinitions
   */
  protected function mtdrLogTaskDefinitions(
    LoggerInterface $logger,
    string $pipelineId,
    array $taskDefinitions,
  ): static {
    $logArgs = [
      'pipeline.id' => $pipelineId,
    ];

    if (!$taskDefinitions) {
      $logger->notice(
        'there are no tasks for event: {pipeline.id}',
        $logArgs,
      );

      return $this;
    }

    $output = new BufferedOutput();
    $table = $this->utils->taskDefinitionsAsTable($taskDefinitions, $output);
    $table->render();
    $logArgs['taskDefinitionsTable'] = $output->fetch();
    // @todo Should be this a "debug" message?
    // StdOutput will be noisy otherwise.
    $logger->notice(
      "Pipeline ID: {pipeline.id}\n{taskDefinitionsTable}",
      $logArgs,
    );

    return $this;
  }

}

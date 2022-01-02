<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin;

use Robo\Collection\CollectionBuilder;
use Sweetchuck\Robo\PHPUnit\PHPUnitTaskLoader;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

class PhpunitCommandsBase extends CommandsBase {

  use PHPUnitTaskLoader;

  protected static string $classKeyPrefix = 'marvin.phpunit';

  protected string $customEventNamePrefix = 'marvin:phpunit';

  /**
   * @return \Sweetchuck\Robo\PHPUnit\Task\RunTask|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskPhpUnit(array $options): CollectionBuilder {
    $this->initComposerInfo();
    $options += [
      'phpunitExecutable' => $this->composerInfo['config']['bin-dir'] . '/phpunit',
    ];

    $task = $this->taskPHPUnitRun($options);

    $gitHook = $this->getConfig()->get('marvin.gitHook');
    if ($gitHook === 'pre-commit') {
      $task->setNoCoverage(TRUE);
      // @todo $task->setNoLogging(true);
    }

    return $task;
  }

  protected function geDefaultPhpunitTaskOptions(?array $phpVariant = NULL): array {
    $options = [
      'colors' => $this->getColors(),
      'processTimeout' => NULL,
      'phpExecutable' => $phpVariant['phpExecutable'],
    ];

    if (!empty($phpVariant['phpExecutable'])) {
      $options['phpunitExecutable'] = $this->getPhpUnitExecutable();
    }

    return $options;
  }

  protected function getPhpUnitExecutable(): string {
    $composerInfo = $this->getComposerInfo();

    return $composerInfo['config']['bin-dir'] . '/phpunit';
  }

  protected function getTestSuiteNamesByEnvironmentVariant(): ?array {
    $environmentVariants = $this->getEnvironmentVariants();

    $testSuites = NULL;
    foreach ($environmentVariants as $environmentVariant) {
      $testSuites = $this->getConfigValue("testSuite.$environmentVariant");
      if ($testSuites !== NULL) {
        break;
      }
    }

    if ($testSuites === FALSE) {
      // Do not run any phpunit tests.
      return NULL;
    }

    if ($testSuites === TRUE || $testSuites === NULL) {
      // Run all phpunit tests.
      return [];
    }

    return array_keys(array_filter($testSuites, new ArrayFilterEnabled()));
  }

  protected function getColors(): string {
    $state = $this->getTriStateOptionValue('ansi');

    if ($state === NULL) {
      $state = 'auto';
    }

    return $state ? 'always' : 'never';
  }

}

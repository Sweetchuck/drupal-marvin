<?php

namespace Drush\Commands\marvin;

use Robo\Collection\CollectionBuilder;
use Sweetchuck\Robo\PHPUnit\PHPUnitTaskLoader;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

class PhpunitCommandsBase extends CommandsBase {

  use PHPUnitTaskLoader;

  protected static function getClassKey(string $key): string {
    return static::configPrefix() . $key;
  }

  /**
   * {@inheritdoc}
   */
  protected static function configPrefix() {
    return 'marvin.phpunit.';
  }

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':test:phpunit';
  }

  /**
   * @return \Sweetchuck\Robo\PHPUnit\Task\RunTask|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskPhpUnit(array $testSuiteNames, array $groupNames, array $phpVariant): CollectionBuilder {
    $task = $this
      ->taskPHPUnitRun()
      ->setPhpExecutable("{$phpVariant['phpdbgExecutable']} -qrr")
      ->setPhpunitExecutable($this->getPhpUnitExecutable())
      ->setProcessTimeout(NULL)
      ->setColors('always')
      ->setTestSuite($testSuiteNames)
      ->setGroup($groupNames);

    $gitHook = $this->getConfig()->get('marvin.gitHook');
    if ($gitHook === 'pre-commit') {
      $task->setNoCoverage(TRUE);
      // @todo $task->setNoLogging(true);
    }

    return $task;
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

}

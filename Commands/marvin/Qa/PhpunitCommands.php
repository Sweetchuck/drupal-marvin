<?php

namespace Drush\Commands\marvin\Qa;

use Drush\Commands\marvin\QaCommandsBase;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Git\Utils;

class PhpunitCommands extends QaCommandsBase {

  /**
   * @command marvin:qa:phpunit
   * @bootstrap none
   */
  public function phpunitRun(): ?TaskInterface {
    $testSuiteNames = $this->getTestSuiteNamesByEnvironmentVariant();
    if ($testSuiteNames === NULL) {
      return NULL;
    }

    $binDir = $this->composerInfo['config']['bin-dir'];

    // @todo Configurable phpdbg executable.
    $cmdPattern = 'phpdbg -qrr %s --verbose';
    $cmdArgs = [
      escapeshellcmd("$binDir/phpunit"),
    ];

    $gitHook = $this->getConfig()->get('command.marvin.settings.gitHook');
    if ($gitHook === 'pre-commit') {
      $cmdPattern .= ' --no-coverage';
    }

    if ($testSuiteNames) {
      $cmdPattern .= ' --testsuite %s';
      $cmdArgs[] = escapeshellarg(implode(',', $testSuiteNames));
    }

    return $this->taskExec(vsprintf($cmdPattern, $cmdArgs));
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

    $testSuites = Utils::filterEnabled($testSuites);

    return $testSuites ? $testSuites : NULL;
  }

}

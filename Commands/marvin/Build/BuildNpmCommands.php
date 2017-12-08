<?php

namespace Drush\Commands\marvin\Build;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\marvin\CommandsBase;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Yarn\YarnTaskLoader;
use Symfony\Component\Console\Command\Command;

class BuildNpmCommands extends CommandsBase {

  use YarnTaskLoader;

  /**
   * @var array
   */
  protected $cliArgs = [];

  /**
   * @var array
   */
  protected $cliOptions = [];

  /**
   * @hook option marvin:build:npm
   */
  public function buildNpmHookOption(Command $command) {
    $this->hookOptionAddArgumentPackages($command);
  }

  /**
   * @hook validate marvin:build:npm
   */
  public function buildNpmHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * @command marvin:build:npm
   */
  public function buildNpm(): TaskInterface {
    $args = func_get_args();
    $options = array_pop($args);

    $argNames = [
      'packages',
    ];

    $this->cliOptions = $options;
    $this->cliArgs = [];
    foreach ($args as $key => $value) {
      $key = $argNames[$key] ?? $key;
      $this->cliArgs[$key] = $value;
    }

    return $this->getTaskBuildNpm();
  }

  protected function getTaskBuildNpm(): TaskInterface {
    if ($this->isIncubatorProject()) {
      $managedDrupalExtensions = $this->getManagedDrupalExtensions();
      $cb = $this->collectionBuilder();
      foreach ($this->cliArgs['packages'] as $packageName) {
        $packagePath = $managedDrupalExtensions[$packageName];
        $cb->addTask($this->getTaskBuildNpmPackage($packagePath));
      }

      return $cb;
    }

    return $this->getTaskBuildNpmPackage('.');
  }

  protected function getTaskBuildNpmPackage(string $packagePath): TaskInterface {
    $yarnExecutable = $this
      ->getConfig()
      ->get('commands.marvin.settings.yarnExecutable');

    return $this
      ->taskYarnInstall()
      ->setWorkingDirectory($packagePath)
      ->setYarnExecutable($yarnExecutable)
      ->setSkipIfPackageJsonNotExists(TRUE);
  }

}

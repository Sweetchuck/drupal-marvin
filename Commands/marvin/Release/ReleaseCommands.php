<?php

namespace Drush\Commands\marvin\Release;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\marvin\CommandsBase;
use Drush\marvin\Robo\CopyFilesTaskLoader;
use Drush\marvin\Robo\PrepareDirectoryTaskLoader;
use Drush\marvin\Robo\ReleaseCollectFilesTaskLoader;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Symfony\Component\Console\Command\Command;

class ReleaseCommands extends CommandsBase {

  use CopyFilesTaskLoader;
  use PrepareDirectoryTaskLoader;
  use ReleaseCollectFilesTaskLoader;

  /**
   * @var array
   */
  protected $cliArgs = [];

  /**
   * @var array
   */
  protected $cliOptions = [];

  /**
   * @hook option marvin:release:build
   */
  public function releaseBuildHookOption(Command $command) {
    $this->hookOptionAddArgumentPackages($command);
  }

  /**
   * @hook validate marvin:release:build
   */
  public function releaseBuildHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * @command marvin:release:build
   */
  public function releaseBuild(): TaskInterface {
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

    return $this->getTaskReleaseBuild();
  }

  protected function getTaskReleaseBuild(): TaskInterface {
    $buildDir = $this->getConfig()->get('command.marvin.settings.buildDir');
    if ($this->isIncubatorProject()) {
      $managedDrupalExtensions = $this->getManagedDrupalExtensions();
      $cb = $this->collectionBuilder();
      foreach ($this->cliArgs['packages'] as $packageName) {
        $packagePath = $managedDrupalExtensions[$packageName];
        $cb->addTask($this->getTaskReleaseBuildPackage($packagePath, "$buildDir/$packageName"));
      }

      return $cb;
    }

    return $this->getTaskReleaseBuildPackage('.', $buildDir);
  }

  protected function getTaskReleaseBuildPackage(string $packagePath, string $dstDir): CollectionBuilder {
    return $this
      ->collectionBuilder()
      ->addTask($this->taskMarvinPrepareDirectory(['workingDirectory' => $dstDir]))
      ->addTask($this->taskMarvinReleaseCollectFiles(['packagePath' => $packagePath]))
      ->addTask(
        $this
          ->taskMarvinCopyFiles()
          ->setSrcDir($packagePath)
          ->setDstDir($dstDir)
          ->deferTaskConfiguration('setFiles', 'files')
      );
  }

}

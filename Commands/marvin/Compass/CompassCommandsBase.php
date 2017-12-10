<?php

namespace Drush\Commands\marvin\Compass;

use Drush\Commands\marvin\CommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\Collection\loadTasks as ForEachTaskLoader;
use Sweetchuck\Robo\Compass\CompassTaskLoader;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Webmozart\PathUtil\Path;

class CompassCommandsBase extends CommandsBase {

  use CompassTaskLoader;
  use GitTaskLoader;
  use ForEachTaskLoader;

  protected function getTaskCompassCleanPackages(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->getTaskCompassCleanPackage($packageName, $packagePath));
    }

    return $cb;
  }

  protected function getTaskCompassCleanPackage(string $packageName, string $packagePath): CollectionBuilder {
    return $this
      ->collectionBuilder()
      ->addTask($this
        ->taskGitListFiles()
        ->setWorkingDirectory($packagePath)
        ->setPaths(['config.rb', '*/config.rb'])
      )
      ->addTask($this
        ->taskForEach()
        ->deferTaskConfiguration('setIterable', 'files')
        ->iterationMessage('{packagePath}/{key}', ['packagePath' => $packagePath])
        ->withBuilder(function (CollectionBuilder $builder, string $configRbFileName) use ($packagePath) {
          $builder
            ->addTask($this
              ->taskCompassClean()
              ->setWorkingDirectory(Path::join($packagePath, Path::getDirectory($configRbFileName)))
              ->setEnvironment('development')
            );
        })
      );
  }

  protected function getTaskCompassCompilePackages(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->getTaskCompassCompilePackage($packageName, $packagePath));
    }

    return $cb;
  }

  protected function getTaskCompassCompilePackage(string $packageName, string $packagePath): CollectionBuilder {
    return $this
      ->collectionBuilder()
      ->addTask($this
        ->taskGitListFiles()
        ->setWorkingDirectory($packagePath)
        ->setPaths(['config.rb', '*/config.rb'])
      )
      ->addTask($this
        ->taskForEach()
        ->deferTaskConfiguration('setIterable', 'files')
        ->withBuilder(function (CollectionBuilder $builder, string $configRbFileName) use ($packagePath) {
          $builder
            ->addTask($this
              ->taskCompassCompile()
              ->setWorkingDirectory(Path::join($packagePath, Path::getDirectory($configRbFileName)))
            );
        })
      );
  }

}

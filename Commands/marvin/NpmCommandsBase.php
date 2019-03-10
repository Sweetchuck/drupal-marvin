<?php

namespace Drush\Commands\marvin;

use Drupal\marvin\Robo\NodeDetectorTaskLoader;
use Robo\Collection\CollectionBuilder;
use Robo\Collection\loadTasks as ForEachTaskLoader;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Nvm\NvmTaskLoader;
use Sweetchuck\Robo\Yarn\YarnTaskLoader;
use Webmozart\PathUtil\Path;

class NpmCommandsBase extends CommandsBase {

  use ForEachTaskLoader;
  use GitTaskLoader;
  use NvmTaskLoader;
  use YarnTaskLoader;
  use NodeDetectorTaskLoader;

  /**
   * {@inheritdoc}
   */
  protected static $classKeyPrefix = 'marvin.npm';

  /**
   * {@inheritdoc}
   */
  protected $customEventNamePrefix = 'marvin:npm';

  protected function getTaskNpmInstallPackages(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->getTaskNpmInstallPackage($packageName, $packagePath));
    }

    return $cb;
  }

  protected function getTaskNpmInstallPackage(string $packageName, string $packagePath): CollectionBuilder {
    $packagePathRelative = Path::makeRelative($packagePath, getcwd());

    return $this
      ->collectionBuilder()
      ->addTask($this
        ->taskGitListFiles()
        ->setWorkingDirectory($packagePathRelative)
        ->setPaths(['package.json', '*/package.json'])
      )
      ->addTask($this
        ->taskForEach()
        ->iterationMessage('{packagePath}/{key}', ['packagePath' => $packagePathRelative])
        ->deferTaskConfiguration('setIterable', 'files')
        ->withBuilder(function (CollectionBuilder $builder, string $packageJsonFileName) use ($packagePathRelative) {
          $workingDirectory = Path::join($packagePathRelative, Path::getDirectory($packageJsonFileName));

          $builder
            ->addTask($this
              ->taskMarvinNodeDetector()
              ->setWorkingDirectory($workingDirectory)
              ->setRootDirectory($packagePathRelative)
            )
            ->addTask($this
              ->taskYarnInstall()
              ->setWorkingDirectory($workingDirectory)
              ->deferTaskConfiguration('setNodeExecutable', 'nodeDetector.node.executable')
              ->deferTaskConfiguration('setYarnExecutable', 'nodeDetector.yarn.executable')
            );
        })
      );
  }

}

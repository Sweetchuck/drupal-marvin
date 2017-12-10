<?php

namespace Drush\Commands\marvin\Build;

use Drush\Commands\marvin\CommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\Collection\loadTasks as ForEachTaskLoader;
use Robo\State\Data as RoboStateData;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Nvm\NvmTaskLoader;
use Sweetchuck\Robo\Yarn\YarnTaskLoader;
use Webmozart\PathUtil\Path;

class BuildNpmCommandsBase extends CommandsBase {

  use ForEachTaskLoader;
  use GitTaskLoader;
  use NvmTaskLoader;
  use YarnTaskLoader;

  protected function getTaskBuildNpmPackages(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->getTaskBuildNpmPackage($packageName, $packagePath));
    }

    return $cb;
  }

  protected function getTaskBuildNpmPackage(string $packageName, string $packagePath): CollectionBuilder {
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
              ->taskYarnNodeVersion()
              ->setWorkingDirectory($workingDirectory)
              ->setRootDirectory($packagePathRelative)
              ->setAssetNamePrefix('required.node.version.')
            )
            ->addCode(function (RoboStateData $data) use ($packagePathRelative): int {
              // @todo Create a native RoboTask to detect the node and yarn executables.
              $data['nodeExecutable'] = '';
              $data['yarnExecutable'] = 'yarn';

              $nodeVersionFull = $data['required.node.version.full'] ?? NULL;
              if (!$nodeVersionFull) {
                return 0;
              }

              $result = $this
                ->taskNvmWhichTask()
                ->addArgument($nodeVersionFull)
                ->run();

              if (!$result->wasSuccessful()) {
                // @todo Install the required NodeJS version automatically.
                $this->yell(
                  "The required NodeJS version '$nodeVersionFull', which is defined in the '$packagePathRelative' directory is not installed.",
                  40,
                  'red'
                );

                return 1;
              }

              $data['nodeExecutable'] = $result['nvm.which.nodeExecutable'];
              $data['yarnExecutable'] = $result['nvm.which.binDir'] . '/yarn';

              return 0;
            })
            ->addTask($this
              ->taskYarnInstall()
              ->setWorkingDirectory($workingDirectory)
              ->deferTaskConfiguration('setNodeExecutable', 'nodeExecutable')
              ->deferTaskConfiguration('setYarnExecutable', 'yarnExecutable')
            );
        })
      );
  }

}

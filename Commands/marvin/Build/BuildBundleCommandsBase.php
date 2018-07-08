<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin\Build;

use Drupal\marvin\Robo\RubyAndBundleDetectorLoader;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\Robo\Bundler\BundlerTaskLoader;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Rvm\RvmTaskLoader;
use Webmozart\PathUtil\Path;

class BuildBundleCommandsBase extends BuildCommandsBase {

  use BundlerTaskLoader;
  use GitTaskLoader;
  use RubyAndBundleDetectorLoader;
  use RvmTaskLoader;

  protected function getTaskBuildBundlePackages(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->getTaskBuildBundlePackage($packageName, $packagePath));
    }

    return $cb;
  }

  protected function getTaskBuildBundlePackage(string $packageName, string $packagePath): CollectionBuilder {
    $packagePathRelative = Path::makeRelative($packagePath, getcwd());

    return $this
      ->collectionBuilder()
      ->addTask($this
        ->taskGitListFiles()
        ->setWorkingDirectory($packagePathRelative)
        ->setPaths(['Gemfile', '*/Gemfile'])
      )
      ->addTask($this
        ->taskForEach()
        ->iterationMessage('{packagePath}/{key}', ['packagePath' => $packagePathRelative])
        ->deferTaskConfiguration('setIterable', 'files')
        ->withBuilder(function (CollectionBuilder $builder, string $gemFileName) use ($packagePathRelative) {
          $workingDirectory = Path::join($packagePathRelative, Path::getDirectory($gemFileName));

          $builder
            ->addTask($this
              ->taskMarvinRubyAndBundleDetector()
              ->setWorkingDirectory($workingDirectory)
              ->setRootDirectory($packagePathRelative)
            )
            ->addTask($this
              ->taskBundleInstall()
              ->setWorkingDirectory($workingDirectory)
              ->deferTaskConfiguration('setRubyExecutable', 'rubyExecutable')
              ->deferTaskConfiguration('setBundleExecutable', 'bundleExecutable')
            );
        })
      );
  }

}

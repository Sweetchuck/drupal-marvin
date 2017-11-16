<?php

namespace Drush\Commands\Marvin\Release;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\Marvin\CommandsBase;
use Drush\marvin\ComposerInfo;
use Drush\marvin\Robo\CopyFilesTaskLoader;
use Drush\marvin\Robo\PrepareDirectoryTaskLoader;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;

class ReleaseCommands extends CommandsBase {

  use CopyFilesTaskLoader;
  use PrepareDirectoryTaskLoader;

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
    $cb = $this->collectionBuilder();

    $cb->addTask($this->taskMarvinPrepareDirectory(['workingDirectory' => $dstDir]));

    $composerInfo = ComposerInfo::create('composer.json', $packagePath);
    switch ($composerInfo['type']) {
      case 'drupal-module':
      case 'drupal-theme':
      case 'drupal-drush':
        $files = (new Finder())
          ->in($packagePath)
          ->files()
          ->name('composer.json');

        // Include.
        // PHP.
        $files
          ->name('*.php')
          ->name('*.inc')
          ->name('*.module')
          ->name('*.theme')
          ->name('*.profile')
          ->name('*.engine')
          ->notPath($dstDir)
          ->notPath('vendor')
          ->notName('.phpbrewrc')
          ->notName('composer.lock')
          ->notName('phpcs.xml.dist')
          ->notName('phpcs.xml')
          ->notName('phpunit.xml.dist')
          ->notName('phpunit.xml');

        // Config.
        $files->name('*.yml');

        // CSS.
        $files
          ->name('*.css')
          ->name('/^_.+\.scss$/')
          ->name('/^_.+\.sass$/')
          ->notPath('.sass-cache')
          ->notName('config.rb')
          ->notName('.scss-lint.yml');

        // JavaScript.
        $files
          ->name('*.js')
          ->name('*.td.ts')
          ->notPath('node_modules')
          ->notName('.npmignore')
          ->notName('npm-debug.log')
          ->notName('npm-shrinkwrap.json')
          ->notName('package.json')
          ->notName('yarn.lock')
          ->notName('yarn-error.log')
          ->notName('.nvmrc')
          ->notName('.eslintignore')
          ->notName('.eslintrc.json')
          ->notName('bower.json')
          ->notName('.bowerrc')
          ->notName('Gruntfile.js')
          ->notName('gulpfile.js')
          ->notName('tslint.json')
          ->notName('*.ts');

        // Image.
        $files
          ->name('*.png')
          ->name('*.jpeg')
          ->name('*.jpg')
          ->name('*.svg')
          ->name('*.ttf')
          ->name('*.ico');

        // Font.
        $files
          ->name('*.otf')
          ->name('*.woff')
          ->name('*.woff2')
          ->name('*.eot');

        // Git.
        $files
          ->notPath('.git')
          ->notPath('.gtm')
          ->notName('.gitignore');

        // Ruby.
        $files
          ->notPath('.bundle')
          ->notName('.ruby-version')
          ->notName('.rvmrc')
          ->notName('Gemfile')
          ->notName('Gemfile.lock');

        // Docker.
        $files
          ->notName('Dockerfile')
          ->notName('docker-compose.yml')
          ->notName('.dockerignore');

        // CI.
        $files
          ->notName('Jenkinsfile')
          ->notName('.gitlab-ci.yml')
          ->notName('.travis.yml')
          ->notName('circle.yml');

        // OS.
        $files
          ->notName('.directory')
          ->notName('.DS_Store');

        $cb->addTask(
          $this
            ->taskMarvinCopyFiles()
            ->setSrcDir($packagePath)
            ->setDstDir($dstDir)
            ->setFiles($files)
        );
        break;
    }

    return $cb;
  }

}

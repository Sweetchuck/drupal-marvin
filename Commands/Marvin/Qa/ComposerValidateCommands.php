<?php

namespace Drush\Commands\Marvin\Qa;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\Marvin\CommandsBase;
use Robo\Contract\TaskInterface;
use Symfony\Component\Console\Command\Command;

class ComposerValidateCommands extends CommandsBase {

  /**
   * @var array
   */
  protected $cliArgs = [];

  /**
   * @var array
   */
  protected $cliOptions = [];

  /**
   * @hook option marvin:qa:composer:validate
   */
  public function composerValidateHookOption(Command $command) {
    $this->hookOptionAddArgumentPackages($command);
  }

  /**
   * @hook validate marvin:qa:composer:validate
   */
  public function composerValidateHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * @command marvin:qa:composer:validate
   * @bootstrap none
   */
  public function composerValidate(): TaskInterface {
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

    return $this->getTaskComposerValidate();
  }

  protected function getTaskComposerValidate(): TaskInterface {
    $composerExecutable = $this->getConfig()->get('command.marvin.settings.composerExecutable');

    if ($this->isIncubatorProject()) {
      $managedDrupalExtensions = $this->getManagedDrupalExtensions();
      $cb = $this->collectionBuilder();
      foreach ($this->cliArgs['packages'] as $packageName) {
        $packagePath = $managedDrupalExtensions[$packageName];
        $cb->addTask(
          $this
            ->taskComposerValidate($composerExecutable)
            ->dir($packagePath)
        );
      }

      return $cb;
    }

    return $this->taskComposerValidate($composerExecutable);
  }

}

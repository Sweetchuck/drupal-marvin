<?php

namespace Drush\Commands\marvin\Qa;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\marvin\Robo\PhpcsConfigFallbackTaskLoader;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Symfony\Component\Console\Command\Command;
use Webmozart\PathUtil\Path;

class LintPhpcsCommands extends LintCommandsBase {

  use PhpcsTaskLoader;
  use PhpcsConfigFallbackTaskLoader;
  use GitTaskLoader;

  /**
   * @hook option marvin:qa:lint:phpcs
   */
  public function lintPhpcsHookOption(Command $command) {
    $this->hookOptionAddArgumentPackages($command);
  }

  /**
   * @hook validate marvin:qa:lint:phpcs
   */
  public function lintPhpcsHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * Runs PHP_CodeSniffer.
   *
   * @command marvin:qa:lint:phpcs
   * @bootstrap none
   */
  public function lintPhpcs(): ?TaskInterface {
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

    return $this->getTaskLintPhpcs();
  }

  /**
   * @return null|\Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskLintPhpcs(): TaskInterface {
    if ($this->isIncubatorProject()) {
      $managedDrupalExtensions = $this->getManagedDrupalExtensions();
      $cb = $this->collectionBuilder();
      foreach ($this->cliArgs['packages'] as $packageName) {
        $packagePath = $managedDrupalExtensions[$packageName];
        $cb->addTask($this->getTaskLintPhpcsExtension($packagePath));
      }

      return $cb;
    }

    return $this->getTaskLintPhpcsExtension('.');
  }

  /**
   * @return null|\Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskLintPhpcsExtension(string $workingDirectory): ?TaskInterface {
    $gitHook = $this->getConfig()->get('command.marvin.settings.gitHook');
    $phpcsXml = $this->getPhpcsConfigurationFileName($workingDirectory);

    $presetName = $this->getPresetNameByEnvironmentVariant();
    $options = $this->getConfigValue("preset.$presetName");
    if ($phpcsXml) {
      unset($options['standards']);
    }

    $options['phpcsExecutable'] = Path::join(
      $this->makeRelativePathToComposerBinDir($workingDirectory),
      'phpcs'
    );
    $options['workingDirectory'] = $workingDirectory;
    $options += ['lintReporters' => []];
    $options['lintReporters'] += $this->getLintReporters();

    if ($gitHook === 'pre-commit') {
      return $this
        ->collectionBuilder()
        ->addTask($this
          ->taskPhpcsParseXml()
          ->setWorkingDirectory($workingDirectory)
          ->setFailOnXmlFileNotExists(FALSE)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskMarvinPhpcsConfigFallback()
          ->setWorkingDirectory($workingDirectory)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskGitReadStagedFiles()
          ->setCommandOnly(TRUE)
          ->deferTaskConfiguration('setPaths', 'phpcsXml.files'))
        ->addTask($this
          ->taskPhpcsLintInput($options)
          ->deferTaskConfiguration('setFiles', 'files')
          ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
    }

    if (!$phpcsXml) {
      return $this
        ->collectionBuilder()
        ->addTask($this
          ->taskMarvinPhpcsConfigFallback()
          ->setWorkingDirectory($workingDirectory)
          ->setAssetNamePrefix('phpcsXml.'))
        ->addTask($this
          ->taskPhpcsLintFiles($options)
          ->deferTaskConfiguration('setFiles', 'phpcsXml.files')
          ->deferTaskConfiguration('setIgnore', 'phpcsXml.exclude-patterns'));
    }

    return $this->taskPhpcsLintFiles($options);
  }

  protected function getPhpcsConfigurationFileName(string $directory): string {
    $directory = $directory ?? '.';
    $candidates = [
      'phpcs.xml',
      'phpcs.xml.dist',
    ];

    foreach ($candidates as $candidate) {
      $fileName = Path::join($directory, $candidate);
      if (file_exists($fileName)) {
        return $fileName;
      }
    }

    return '';
  }

}

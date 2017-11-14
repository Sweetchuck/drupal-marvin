<?php

namespace Drush\Commands\Marvin\Qa;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\marvin\ArrayUtils\FileSystemArrayUtils;
use Drush\marvin\ComposerInfo;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Symfony\Component\Console\Command\Command;
use Webmozart\PathUtil\Path;

class LintPhpcsCommands extends LintCommandsBase {

  use PhpcsTaskLoader;
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
      // @todo Get file paths from the drush.yml configuration.
      $paths = $phpcsXml ? $this->getFilePathsFromXml($phpcsXml)
        : $paths = $this->getFilePathsByProjectType($workingDirectory);

      return $this
        ->collectionBuilder()
        ->addTask($this
          ->taskGitReadStagedFiles()
          ->setCommandOnly(TRUE)
          ->setPaths($paths['files']))
        ->addTask($this
          ->taskPhpcsLintInput($options)
          ->setIgnore($paths['exclude-patterns'])
          ->deferTaskConfiguration('setFiles', 'files'));
    }

    $task = $this->taskPhpcsLintFiles($options);
    if (!$phpcsXml) {
      $filePaths = $this->getFilePathsByProjectType($workingDirectory);
      $task
        ->setFiles($filePaths['files'])
        ->setIgnore($filePaths['exclude-patterns']);
    }

    return $task;
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

  protected function getFilePathsByProjectType(string $workingDirectory): array {
    $workingDirectory = $workingDirectory ?? '.';
    $filePaths = [
      'files' => [],
      'exclude-patterns' => [],
    ];

    $composerInfo = ComposerInfo::create("$workingDirectory/composer.json");
    switch ($composerInfo['type']) {
      case 'project':
      case 'drupal-project':
        break;

      case 'drupal-module':
      case 'drupal-theme':
      case 'drupal-drush':
        // @todo Autodetect PHP files.
        $filePaths['files']['Commands/'] = TRUE;
        $filePaths['files']['src/'] = TRUE;
        $filePaths['files']['tests/'] = TRUE;
        break;

      case 'drupal-profile':
        break;

    }

    $arrayUtils = new FileSystemArrayUtils(NULL, ['baseDir' => $workingDirectory]);
    array_walk($filePaths['files'], [$arrayUtils, 'walkExists']);

    return $filePaths;
  }

  protected function getFilePathsFromXml(string $phpcsXmlFileName): array {
    $xml = new \DOMDocument();
    $xml->loadXML(file_get_contents($phpcsXmlFileName));
    $xpath = new \DOMXPath($xml);

    $xpathQueries = [
      'files' => '/ruleset/file',
      'exclude-patterns' => '/ruleset/exclude-pattern',
    ];

    $paths = array_fill_keys(array_keys($xpathQueries), []);
    foreach ($xpathQueries as $key => $query) {
      $elements = $xpath->query($query);
      /** @var \DOMNode $element */
      foreach ($elements as $element) {
        $paths[$key][$element->textContent] = TRUE;
      }
    }

    return $paths;
  }

}

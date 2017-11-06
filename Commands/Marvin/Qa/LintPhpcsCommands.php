<?php

namespace Drush\Commands\Marvin\Qa;

use Consolidation\AnnotatedCommand\AnnotationData;
use Drush\marvin\ArrayUtils\FileSystemArrayUtils;
use Drush\marvin\ComposerInfo;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Phpcs\PhpcsTaskLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Webmozart\PathUtil\Path;

class LintPhpcsCommands extends LintCommandsBase {

  use PhpcsTaskLoader;
  use GitTaskLoader;

  /**
   * @hook option marvin:qa:lint:phpcs
   */
  public function lintPhpcsOptionExtensions(Command $command, AnnotationData $annotationData) {
    $definition = $command->getDefinition();
    switch ($this->composerInfo['type']) {
      case 'project':
      case 'drupal-project':
        if (!$definition->hasArgument('extensions')) {
          $command->addArgument(
            'extensions',
            InputArgument::IS_ARRAY,
            'Theme or module machine-names'
          );
        }
        break;

      case 'drupal-drush':
        // @todo Implement.
        break;

      default:
        $command->setHidden(TRUE);
        break;

    }
  }

  /**
   * @hook pre-validate marvin:qa:lint:phpcs
   */
  public function lintPhpcsPreValidate() {
    $supportedProjectTypes = $this->lintGetSupportedProjectTypes();
    if (!in_array($this->composerInfo['type'], $supportedProjectTypes)) {
      throw new \Exception(sprintf(
        'PHP Code sniffer is not supported for "%s" project type. The supported project types are: %s',
        $this->composerInfo['type'],
        implode(', ', $supportedProjectTypes)
      ));
    }
  }

  /**
   * @command marvin:qa:lint:phpcs
   * @bootstrap none
   */
  public function lintPhpcs(): ?TaskInterface {
    $args = func_get_args();
    $options = array_pop($args);

    $this->cliArgs = $args;
    $this->cliOptions = $options;

    return $this->getTaskLintPhpcs();
  }

  /**
   * @return null|\Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskLintPhpcs(): ?TaskInterface {
    switch ($this->composerInfo['type']) {
      case 'project':
      case 'drupal-project':
        $workingDirectory = 'web/core/modules/action';
        $relativeBinDir = Path::makeRelative($this->composerInfo['config']['bin-dir'], $workingDirectory);

        return $this
          ->taskPhpcsLintFiles()
          ->setWorkingDirectory($workingDirectory)
          ->setPhpcsExecutable("$relativeBinDir/phpcs")
          ->setStandards(['Drupal', 'DrupalPractice'])
          ->setLintReporters([
            'lintVerboseReporter' => NULL,
          ])
          ->setFiles([
            'src/',
            'action.module',
          ]);

      case 'drupal-drush':
        return $this->getTaskLintPhpcsExtension('.');

    }

    return NULL;
  }

  /**
   * @return null|\Robo\Contract\TaskInterface|\Robo\Collection\CollectionBuilder
   */
  protected function getTaskLintPhpcsExtension(string $workingDirectory): ?TaskInterface {
    $config = $this->getConfig();
    $environment = $config->get('command.marvin.settings.environment');
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

    $options += ['lintReporters' => []];
    $options['lintReporters'] += $this->getLintReporters($environment);

    if ($environment === 'gitHook-pre-commit') {
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
        $filePaths['files'][] = 'Commands/';
        $filePaths['files'][] = 'src/';
        $filePaths['files'][] = 'tests/';
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

<?php

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Utils as MarvinUtils;
use League\Container\ContainerInterface;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;
use Sweetchuck\Utils\Walker\FileSystemExistsWalker;

class PhpcsConfigFallbackTask extends BaseTask implements StateAwareInterface {

  use StateAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - PHP_CodeSniffer config fallback';

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    parent::setContainer($container);

    $pairs = [
      'marvin.file_system_exists_walker' => FileSystemExistsWalker::class,
    ];

    foreach ($pairs as $alias => $class) {
      if (!$container->has($alias)) {
        $container->add($alias, $class);
      }
    }

    return $this;
  }

  /**
   * @var string
   */
  protected $workingDirectory = '';

  public function getWorkingDirectory(): string {
    return $this->workingDirectory;
  }

  /**
   * @return $this
   */
  public function setWorkingDirectory(string $value) {
    $this->workingDirectory = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('workingDirectory', $options)) {
      $this->setWorkingDirectory($options['workingDirectory']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    /** @var \Robo\State\Data $state */
    $state = $this->getState();

    $assetNamePrefix = $this->getAssetNamePrefix();
    if (isset($state["{$assetNamePrefix}files"]) || isset($state["{$assetNamePrefix}exclude-patterns"])) {
      $this->printTaskDebug('The PHPCS config is already available from state data.');

      return $this;
    }

    $workingDirectory = $this->getWorkingDirectory() ?? '.';
    $this->assets = $this->getFilePathsByProjectType($workingDirectory);

    return $this;
  }

  protected function getFilePathsByProjectType(string $workingDirectory): array {
    // @todo Get file paths from the drush.yml configuration.
    $composerInfo = ComposerInfo::create($workingDirectory);
    $filePaths = [
      'files' => [],
      'exclude-patterns' => [],
    ];
    switch ($composerInfo['type']) {
      case 'project':
      case 'drupal-project':
        // @todo
        break;

      case 'drupal-module':
      case 'drupal-theme':
      case 'drupal-drush':
        $filePaths['files']['Commands/'] = TRUE;
        $filePaths['files']['src/'] = TRUE;
        $filePaths['files']['tests/'] = TRUE;
        $filePaths['files'] += array_fill_keys(
          MarvinUtils::getDirectDescendantDrupalPhpFiles($workingDirectory),
          TRUE
        );
        break;

      case 'drupal-profile':
        $filePaths['files']['Commands/'] = TRUE;
        $filePaths['files']['src/'] = TRUE;
        $filePaths['files']['tests/'] = TRUE;
        $filePaths['files']['modules/custom/'] = TRUE;
        $filePaths['files']['themes/custom/'] = TRUE;
        $filePaths['files'] += array_fill_keys(
          MarvinUtils::getDirectDescendantDrupalPhpFiles($workingDirectory),
          TRUE
        );
        break;
    }

    $walker = $this->getContainer()->get('marvin.file_system_exists_walker');
    $walker->setBaseDir($workingDirectory);
    array_walk($filePaths['files'], $walker);

    return $filePaths;
  }

}

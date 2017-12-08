<?php

namespace Drush\marvin\Robo\Task;

use Drush\marvin\ArrayUtils\FileSystemArrayUtils;
use Drush\marvin\ComposerInfo;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;

class PhpcsConfigFallbackTask extends BaseTask implements StateAwareInterface {

  use StateAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - PHP_CodeSniffer config fallback';

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

    $key = 'workingDirectory';
    if (array_key_exists($key, $options)) {
      $this->setWorkingDirectory($options[$key]);
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
      $this->printTaskDebug('The PHPCS config is already available from as state data.');

      return $this;
    }

    $workingDirectory = $this->getWorkingDirectory() ?? '.';
    $this->assets = $this->getFilePathsByProjectType($workingDirectory);

    return $this;
  }

  protected function getFilePathsByProjectType(string $workingDirectory): array {
    // @todo Get file paths from the drush.yml configuration.
    $composerInfo = ComposerInfo::create("$workingDirectory/composer.json");
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
        // @todo Autodetect PHP files.
        $filePaths['files']['Commands/'] = TRUE;
        $filePaths['files']['src/'] = TRUE;
        $filePaths['files']['tests/'] = TRUE;
        break;

      case 'drupal-profile':
        // @todo
        break;
    }

    $arrayUtils = new FileSystemArrayUtils(NULL, ['baseDir' => $workingDirectory]);
    array_walk($filePaths['files'], [$arrayUtils, 'walkExists']);

    return $filePaths;
  }

}

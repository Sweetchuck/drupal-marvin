<?php

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\Utils;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;

/**
 * @deprecated Use a NodeJS based SCSS linter and compiler instead.
 */
class ScssLintConfigFallbackTask extends BaseTask implements StateAwareInterface {

  use StateAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - SCSS lint config fallback';

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
    $assetName = "{$assetNamePrefix}scssLintYmlFilePath";
    if (isset($state[$assetName])) {
      $this->printTaskDebug('The SCSS lint config file name is already available from state data.');

      return $this;
    }

    // @todo Find a better way.
    $projectRootDir = getcwd();

    $dirs = [
      $this->getWorkingDirectory() ?? '.',
      "$projectRootDir/etc",
      $projectRootDir,
      Utils::marvinRootDir() . '/etc',
    ];

    foreach ($dirs as $dir) {
      if (file_exists("$dir/.scss-lint.yml")) {
        // @todo Relative from $workingDirectory.
        $this->assets[$assetName] = "$dir/.scss-lint.yml";

        break;
      }
    }

    return $this;
  }

}

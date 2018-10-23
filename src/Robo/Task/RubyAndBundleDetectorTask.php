<?php

declare(strict_types = 1);

namespace Drupal\marvin\Robo\Task;

use Robo\Common\BuilderAwareTrait;
use Robo\Contract\BuilderAwareInterface;
use Sweetchuck\Robo\Bundler\BundlerTaskLoader;
use Sweetchuck\Robo\Rvm\RvmTaskLoader;

/**
 * @deprecated Use a NodeJS based SCSS linter and compiler instead.
 */
class RubyAndBundleDetectorTask extends BaseTask implements BuilderAwareInterface {

  use BuilderAwareTrait;
  use BundlerTaskLoader;
  use RvmTaskLoader;

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Ruby and Bundle executable detector';

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
   * @var string
   */
  protected $rootDirectory = '';

  public function getRootDirectory(): string {
    return $this->rootDirectory;
  }

  /**
   * @return $this
   */
  public function setRootDirectory(string $value) {
    $this->rootDirectory = $value;

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

    if (array_key_exists('rootDirectory', $options)) {
      $this->setRootDirectory($options['rootDirectory']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function initOptions() {
    parent::initOptions();

    $this->options['workingDirectory'] = [
      'type' => 'other',
      'value' => $this->getWorkingDirectory(),
    ];

    $this->options['rootDirectory'] = [
      'type' => 'other',
      'value' => $this->getRootDirectory(),
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $this->assets['rubyExecutable'] = '';
    $this->assets['bundleExecutable'] = 'bundle';

    $rubyVersionBase = $this->getRubyVersionBaseWithBundlePlatform();
    if (!$rubyVersionBase) {
      $rubyVersionBase = $this->getRubyVersionBaseWithRvmDetect();
    }

    if (!$rubyVersionBase) {
      return $this;
    }

    $rvmInfo = $this->getRvmInfo([$rubyVersionBase]);
    $firstInstance = reset($rvmInfo);
    if ($firstInstance) {
      $this->assets['rubyExecutable'] = $firstInstance['binaries']['ruby'] ?? '';
      $this->assets['bundleExecutable'] = !empty($firstInstance['homes']['gem']) ?
        "{$firstInstance['homes']['gem']}/bin/bundle"
        : 'bundle';
    }

    return $this;
  }

  protected function getRubyVersionBaseWithBundlePlatform(): string {
    $result = $this
      ->taskBundlePlatformRubyVersion()
      ->setWorkingDirectory($this->options['workingDirectory']['value'])
      ->setAssetNamePrefix('ruby-version.')
      ->run()
      ->stopOnFail();

    return $result['ruby-version.base'] ?? '';
  }

  protected function getRubyVersionBaseWithRvmDetect(): string {
    $result = $this
      ->taskRvmDetectRubyVersion()
      ->setWorkingDirectory($this->options['workingDirectory']['value'])
      ->setRootDirectory($this->options['rootDirectory']['value'])
      ->run()
      ->stopOnFail();

    return $result['ruby-version'] ?? '';
  }

  protected function getRvmInfo(array $rubyStrings): array {
    $rvmInfoResult = $this
      ->taskRvmInfo()
      ->setRubyStrings($rubyStrings)
      ->run()
      ->stopOnFail();

    return $rvmInfoResult['rvm.info'] ?? [];
  }

}

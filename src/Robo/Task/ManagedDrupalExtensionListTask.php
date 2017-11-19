<?php

namespace Drush\marvin\Robo\Task;

use Drush\marvin\ComposerInfo;
use Drush\marvin\Utils;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Webmozart\PathUtil\Path;

class ManagedDrupalExtensionListTask extends BaseTask implements
    ContainerAwareInterface,
    OutputAwareInterface {

  use ContainerAwareTrait;
  use IO;

  /**
   * @var string
   */
  protected $assetNamePrefix = '';

  public function getAssetNamePrefix(): string {
    return $this->assetNamePrefix;
  }

  /**
   * @return $this
   */
  public function setAssetNamePrefix(string $value) {
    $this->assetNamePrefix = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $workingDirectory = '.';

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
  protected $composerJsonFileName = 'composer.json';

  public function getComposerJsonFileName(): string {
    return $this->composerJsonFileName;
  }

  /**
   * @return $this
   */
  public function setComposerJsonFileName(string $value) {
    $this->composerJsonFileName = $value;

    return $this;
  }

  /**
   * @var array
   */
  protected $packagePaths = [];

  public function getPackagePaths(): array {
    return $this->packagePaths;
  }

  /**
   * @return $this
   */
  public function setPackagePaths(array $value) {
    $this->packagePaths = $value;

    return $this;
  }

  /**
   * @var array
   */
  protected $ignoredPackages = [];

  public function getIgnoredPackages(): array {
    return $this->ignoredPackages;
  }

  /**
   * @return $this
   */
  public function setIgnoredPackages(array $value) {
    $this->ignoredPackages = $value;

    return $this;
  }

  /**
   * @var \Drush\marvin\ComposerInfo
   */
  protected $composerInfo;

  public function __construct(array $options = []) {
    $this->setOptions($options);
  }

  public function setOptions(array $options) {
    foreach ($options as $key => $value) {
      switch ($key) {
        case 'assetNamePrefix':
          $this->setAssetNamePrefix($value);
          break;

        case 'workingDirectory':
          $this->setWorkingDirectory($value);
          break;

        case 'composerJsonFileName':
          $this->setComposerJsonFileName($value);
          break;

        case 'packagePaths':
          $this->setPackagePaths($value);
          break;

        case 'ignoredPackages':
          $this->setIgnoredPackages($value);
          break;

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->composerInfo = ComposerInfo::create(
      $this->getWorkingDirectory(),
      $this->getComposerJsonFileName()
    );

    $managedDrupalExtensions = Utils::collectManagedDrupalExtensions(
      Path::makeAbsolute($this->composerInfo->getWorkingDirectory(), getcwd()),
      $this->composerInfo->getLock(),
      $this->getPackagePaths()
    );

    $managedDrupalExtensions = array_diff_key(
      $managedDrupalExtensions,
      array_flip($this->getIgnoredPackages())
    );

    $assetNamePrefix = $this->getAssetNamePrefix();

    return Result::success(
      $this,
      '',
      [
        "{$assetNamePrefix}managedDrupalExtensions" => $managedDrupalExtensions,
      ]
    );
  }

}

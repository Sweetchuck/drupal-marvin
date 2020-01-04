<?php

declare(strict_types = 1);

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\Utils as MarvinUtils;
use InvalidArgumentException;
use Stringy\StaticStringy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class VersionNumberBumpExtensionInfoTask extends BaseTask {

  const ERROR_CODE_VERSION_NUMBER_EMPTY = 1;

  const ERROR_CODE_VERSION_NUMBER_INVALID = 2;

  const ERROR_CODE_PACKAGE_PATH_EMPTY = 3;

  const ERROR_CODE_PACKAGE_PATH_NOT_EXISTS = 4;

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Bump version number - extension info';

  /**
   * @var string
   */
  protected $packagePath = '';

  public function getPackagePath(): string {
    return $this->packagePath;
  }

  /**
   * @return $this
   */
  public function setPackagePath(string $value) {
    $this->packagePath = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $versionNumber = '';

  public function getVersionNumber(): string {
    return $this->versionNumber;
  }

  /**
   * Drupal version number.
   *
   * Example value: "8.x-1.2".
   *
   * @return $this
   */
  public function setVersionNumber(string $value) {
    $this->versionNumber = $value;

    return $this;
  }

  /**
   * @var bool
   */
  protected $bumpExtensionInfo = TRUE;

  public function getBumpExtensionInfo(): bool {
    return $this->bumpExtensionInfo;
  }

  /**
   * @return $this
   */
  public function setBumpExtensionInfo(bool $value) {
    $this->bumpExtensionInfo = $value;

    return $this;
  }

  /**
   * @var bool
   */
  protected $bumpComposerJson = TRUE;

  public function getBumpComposerJson(): bool {
    return $this->bumpComposerJson;
  }

  /**
   * @return $this
   */
  public function setBumpComposerJson(bool $value) {
    $this->bumpComposerJson = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('packagePath', $options)) {
      $this->setPackagePath($options['packagePath']);
    }

    if (array_key_exists('versionNumber', $options)) {
      $this->setVersionNumber($options['versionNumber']);
    }

    if (array_key_exists('bumpExtensionInfo', $options)) {
      $this->setBumpExtensionInfo($options['bumpExtensionInfo']);
    }

    if (array_key_exists('bumpComposerJson', $options)) {
      $this->setBumpComposerJson($options['bumpComposerJson']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function initOptions() {
    parent::initOptions();
    $this->options += [
      'packagePath' => [
        'type' => 'other',
        'value' => $this->getPackagePath(),
      ],
      'versionNumber' => [
        'type' => 'other',
        'value' => $this->getVersionNumber(),
      ],
      'bumpExtensionInfo' => [
        'type' => 'other',
        'value' => $this->getBumpExtensionInfo(),
      ],
      'bumpComposerJson' => [
        'type' => 'other',
        'value' => $this->getBumpComposerJson(),
      ],
    ];

    return $this;
  }

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  public function __construct(?Filesystem $fs = NULL) {
    $this->fs = $fs ?: new Filesystem();
  }

  /**
   * {@inheritdoc}
   */
  protected function runHeader() {
    // @todo These placeholders are not working.
    $this->printTaskInfo(
      'Bump version number to "<info>{versionNumber}</info>" in "<info>{packagePath}</info>" directory.',
      [
        'versionNumber' => $this->options['versionNumber']['value'],
        'packagePath' => $this->options['packagePath']['value'],
      ]
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runValidate() {
    parent::runValidate();

    return $this
      ->runValidatePackagePath()
      ->runValidateVersionNumber();
  }

  /**
   * @return $this
   */
  protected function runValidatePackagePath() {
    $packagePath = $this->options['packagePath']['value'];
    if (!$packagePath) {
      throw new InvalidArgumentException(
        'The package path cannot be empty.',
        static::ERROR_CODE_PACKAGE_PATH_EMPTY
      );
    }

    if (!is_dir($packagePath)) {
      throw new InvalidArgumentException(
        sprintf('The package path "%s" is not exists.', $packagePath),
        static::ERROR_CODE_PACKAGE_PATH_NOT_EXISTS
      );
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function runValidateVersionNumber() {
    $versionNumber = $this->options['versionNumber']['value'];
    if (!$versionNumber) {
      throw new InvalidArgumentException(
        'The version number cannot be empty.',
        static::ERROR_CODE_VERSION_NUMBER_EMPTY
      );
    }

    if (!MarvinUtils::isValidDrupalExtensionVersionNumber($versionNumber)) {
      // @todo Give a hint what's the problem with given version number.
      throw new InvalidArgumentException(
        sprintf('The version number "%s" is invalid.', $versionNumber),
        static::ERROR_CODE_VERSION_NUMBER_INVALID
      );
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function runAction() {
    return $this
      ->runActionExtensionInfo()
      ->runActionComposerJson();
  }

  /**
   * @return $this
   */
  protected function runActionExtensionInfo() {
    $packagePath = $this->options['packagePath']['value'];
    $versionNumber = $this->options['versionNumber']['value'];

    if (!$this->options['bumpExtensionInfo']['value']) {
      $this->printTaskDebug(
        'Skip update version number to "<info>{versionNumber}</info>" in "<info>{pattern}</info>" files.',
        [
          'versionNumber' => $versionNumber,
          'pattern' => "$packagePath/*.info.yml",
        ]
      );

      return $this;
    }

    // @todo Support for sub-modules.
    $files = (new Finder())
      ->in($this->options['packagePath']['value'])
      ->files()
      ->depth('== 0')
      ->name('*.info.yml');

    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($files as $file) {
      $this->printTaskDebug(
        'Update version number to "<info>{versionNumber}</info>" in "<info>{file}</info>" file.',
        [
          'versionNumber' => $versionNumber,
          'file' => $packagePath . '/' . $file->getRelativePathname(),
        ]
      );

      $this->fs->dumpFile(
        $file->getPathname(),
        MarvinUtils::changeVersionNumberInYaml($file->getContents(), $versionNumber)
      );
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function runActionComposerJson() {
    $versionNumber = MarvinUtils::drupalToSemver($this->options['versionNumber']['value']);
    $composerJsonFilePath = "{$this->options['packagePath']['value']}/composer.json";

    if (!$this->fs->exists($composerJsonFilePath)) {
      return $this;
    }

    $logContext = [
      'versionNumber' => $versionNumber,
      'file' => $composerJsonFilePath,
    ];

    if (!$this->options['bumpComposerJson']['value']) {
      $this->printTaskDebug(
        'Skip update version number to "<info>{versionNumber}</info>" in "<info>{file}</info>" file.',
        $logContext
      );

      return $this;
    }

    $this->printTaskDebug(
      'Update version number to "<info>{versionNumber}</info>" in "<info>{file}</info>" file.',
      $logContext
    );

    $composerInfo = json_decode(file_get_contents($composerJsonFilePath), TRUE);
    $composerInfo['version'] = $versionNumber;

    $jsonString = json_encode($composerInfo, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $this->fs->dumpFile(
      $composerJsonFilePath,
      StaticStringy::ensureRight($jsonString, "\n")
    );

    return $this;
  }

}

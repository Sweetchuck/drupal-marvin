<?php

declare(strict_types = 1);

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

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

    if (array_key_exists('versionNumber', $options)) {
      $this->setVersionNumber($options['versionNumber']);
    }

    if (array_key_exists('bumpExtensionInfo', $options)) {
      $this->setVersionNumber($options['bumpExtensionInfo']);
    }

    if (array_key_exists('bumpComposerJson', $options)) {
      $this->setVersionNumber($options['bumpComposerJson']);
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
      throw new \InvalidArgumentException(
        'The package path cannot be empty.',
        static::ERROR_CODE_PACKAGE_PATH_EMPTY
      );
    }

    if (!is_dir($packagePath)) {
      throw new \InvalidArgumentException(
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
      throw new \InvalidArgumentException(
        'The version number cannot be empty.',
        static::ERROR_CODE_VERSION_NUMBER_EMPTY
      );
    }

    if (!Utils::isValidDrupalExtensionVersionNumber($versionNumber)) {
      // @todo Give a hint what's the problem with given version number.
      throw new \InvalidArgumentException(
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

  protected function runActionExtensionInfo() {
    if (!$this->options['bumpExtensionInfo']['value']) {
      $this->printTaskDebug('Skip version number bumping in *.info.yml files.');

      return $this;
    }

    $files = (new Finder())
      ->in($this->options['packagePath']['value'])
      ->files()
      ->name('*.info.yml');

    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($files as $file) {
      $this->fs->dumpFile(
        $file->getPathname(),
        $this->changeVersionNumberInYaml($file->getContents(), $this->options['versionNumber']['value'])
      );
    }

    return $this;
  }

  protected function runActionComposerJson() {
    if (!$this->options['bumpComposerJson']['value']) {
      $this->printTaskDebug('Skip version number bumping in composer.json.');

      return $this;
    }

    $composerJsonFilePath = "{$this->options['packagePath']['value']}/composer.json";
    $parts = Utils::parseDrupalExtensionVersionNumber($this->options['versionNumber']['value']);
    $composerInfo = json_decode(file_get_contents($composerJsonFilePath), TRUE);
    $composerInfo['version'] = "{$parts['extensionMajor']}.{$parts['extensionMinor']}.0";

    $jsonString = json_encode($composerInfo, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    Utils::ensureTrailingEol($jsonString);
    $this->fs->dumpFile($composerJsonFilePath, $jsonString);

    return $this;
  }

  /**
   * @todo Move to Utils.
   */
  protected function changeVersionNumberInYaml(string $yamlString, string $versionNumber): string {
    // Yaml::parse() and Yaml::dump() strips the comments.
    $escapedVersionNumber = Utils::escapeYamlValueString($versionNumber);

    $value = Yaml::parse($yamlString);
    if (array_key_exists('version', $value)) {
      return preg_replace(
        '/(?=version: ).+/sm',
        $escapedVersionNumber . PHP_EOL,
        $yamlString
      );
    }

    Utils::ensureTrailingEol($yamlString);

    return $yamlString . "version: $escapedVersionNumber" . PHP_EOL;
  }

}

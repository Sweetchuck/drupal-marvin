<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\Utils;
use Sweetchuck\Utils\FileSystemUtils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\UnicodeString;

class UpdateExtensionVersionNumberTask extends BaseTask {

  public const int ERROR_CODE_VERSION_NUMBER_EMPTY = 1;

  public const int ERROR_CODE_VERSION_NUMBER_INVALID = 2;

  public const int ERROR_CODE_PACKAGE_PATH_EMPTY = 3;

  public const int ERROR_CODE_PACKAGE_PATH_NOT_EXISTS = 4;

  protected string $taskName = 'Marvin - Updates version number in a Drupal extension';

  protected string $packagePath = '';

  public function getPackagePath(): string {
    return $this->packagePath;
  }

  public function setPackagePath(string $value): static {
    $this->packagePath = $value;

    return $this;
  }

  protected string $versionNumber = '';

  public function getVersionNumber(): string {
    return $this->versionNumber;
  }

  /**
   * Drupal version number.
   *
   * Example value: "8.x-1.2".
   */
  public function setVersionNumber(string $value): static {
    $this->versionNumber = $value;

    return $this;
  }

  protected bool $updateComposerJson = TRUE;

  public function getUpdateComposerJson(): bool {
    return $this->updateComposerJson;
  }

  public function setUpdateComposerJson(bool $value): static {
    $this->updateComposerJson = $value;

    return $this;
  }

  protected bool $updateExtensionInfo = TRUE;

  public function getUpdateExtensionInfo(): bool {
    return $this->updateExtensionInfo;
  }

  public function setUpdateExtensionInfo(bool $value): static {
    $this->updateExtensionInfo = $value;

    return $this;
  }

  protected bool $updateLibraries = TRUE;

  public function getUpdateLibraries(): bool {
    return $this->updateLibraries;
  }

  public function setUpdateLibraries(bool $value): static {
    $this->updateLibraries = $value;

    return $this;
  }

  /**
   * @phpstan-param marvin-robo-task-version-number-bump-extension-info-options $options
   */
  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('packagePath', $options)) {
      $this->setPackagePath($options['packagePath']);
    }

    if (array_key_exists('versionNumber', $options)) {
      $this->setVersionNumber($options['versionNumber']);
    }

    if (array_key_exists('updateComposerJson', $options)) {
      $this->setUpdateComposerJson($options['updateComposerJson']);
    }

    if (array_key_exists('updateExtensionInfo', $options)) {
      $this->setUpdateExtensionInfo($options['updateExtensionInfo']);
    }

    if (array_key_exists('updateLibraries', $options)) {
      $this->setUpdateLibraries($options['updateLibraries']);
    }

    return $this;
  }

  protected function initOptions(): static {
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
      'updateComposerJson' => [
        'type' => 'other',
        'value' => $this->getUpdateComposerJson(),
      ],
      'updateExtensionInfo' => [
        'type' => 'other',
        'value' => $this->getUpdateExtensionInfo(),
      ],
      'updateLibraries' => [
        'type' => 'other',
        'value' => $this->getUpdateLibraries(),
      ],
    ];

    return $this;
  }

  protected Filesystem $fs;

  protected Utils $utils;

  public function __construct(
    ?Filesystem $fs = NULL,
    ?Utils $utils = NULL,
  ) {
    $this->fs = $fs ?: new Filesystem();
    $this->utils = $utils ?: new Utils(new FileSystemUtils());
  }

  protected function runHeader(): static {
    // @todo These placeholders are not working.
    $this->printTaskInfo(
      'Update version number to "<info>{versionNumber}</info>" in "<info>{packagePath}</info>" directory.',
      [
        'versionNumber' => $this->options['versionNumber']['value'],
        'packagePath' => $this->options['packagePath']['value'],
      ]
    );

    return $this;
  }

  protected function runValidate(): static {
    parent::runValidate();

    return $this
      ->runValidatePackagePath()
      ->runValidateVersionNumber();
  }

  protected function runValidatePackagePath(): static {
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

  protected function runValidateVersionNumber(): static {
    $versionNumber = $this->options['versionNumber']['value'];
    if (!$versionNumber) {
      throw new \InvalidArgumentException(
        'The version number cannot be empty.',
        static::ERROR_CODE_VERSION_NUMBER_EMPTY
      );
    }

    return $this;
  }

  protected function runAction(): static {
    return $this
      ->runActionComposerJson()
      ->runActionExtensionInfo()
      ->runActionExtensionLibraries();
  }

  protected function runActionComposerJson(): static {
    $versionNumber = $this->options['versionNumber']['value'];
    $composerJsonFilePath = "{$this->options['packagePath']['value']}/composer.json";

    if (!$this->fs->exists($composerJsonFilePath)) {
      return $this;
    }

    $logContext = [
      'versionNumber' => $versionNumber,
      'file' => $composerJsonFilePath,
    ];

    if (!$this->options['updateComposerJson']['value']) {
      $this->printTaskDebug(
        'Skip update version number to "<info>{versionNumber}</info>" in "<info>{file}</info>" file.',
        $logContext,
      );

      return $this;
    }

    $this->printTaskDebug(
      'Update version number to "<info>{versionNumber}</info>" in "<info>{file}</info>" file.',
      $logContext,
    );

    $composerInfo = json_decode(
      $this->fs->readFile($composerJsonFilePath) ?: '{}',
      TRUE,
    );
    $composerInfo['version'] = $versionNumber;

    $jsonString = json_encode($composerInfo, \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT) ?: '{}';
    $this->fs->dumpFile(
      $composerJsonFilePath,
      (new UnicodeString($jsonString))
        ->ensureEnd("\n")
        ->toString(),
    );

    return $this;
  }

  protected function runActionExtensionInfo(): static {
    $packagePath = $this->options['packagePath']['value'];
    $versionNumber = $this->options['versionNumber']['value'];

    if (!$this->options['updateExtensionInfo']['value']) {
      $this->printTaskDebug(
        'Skip update version number to "<info>{versionNumber}</info>" in "<info>{pattern}</info>" files.',
        [
          'versionNumber' => $versionNumber,
          'pattern' => "$packagePath/*.info.yml",
        ]
      );

      return $this;
    }

    $files = (new Finder())
      ->in($packagePath)
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
        $this->utils->changeVersionNumberInInfoYaml($file->getContents(), $versionNumber),
      );
    }

    return $this;
  }

  protected function runActionExtensionLibraries(): static {
    $packagePath = $this->options['packagePath']['value'];
    $versionNumber = $this->options['versionNumber']['value'];

    if (!$this->options['updateLibraries']['value']) {
      $this->printTaskDebug(
        'Skip update version number to "<info>{versionNumber}</info>" in "<info>{pattern}</info>" files.',
        [
          'versionNumber' => $versionNumber,
          'pattern' => "$packagePath/*.libraries.yml",
        ]
      );

      return $this;
    }

    $files = (new Finder())
      ->in($packagePath)
      ->files()
      ->depth('== 0')
      ->name('*.libraries.yml');

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
        $this->utils->changeVersionNumberInLibrariesYaml($file->getContents(), $versionNumber),
      );
    }

    return $this;
  }

}

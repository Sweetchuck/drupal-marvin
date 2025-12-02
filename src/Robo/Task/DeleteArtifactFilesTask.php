<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo\Task;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class DeleteArtifactFilesTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected string $taskName = 'Marvin - Delete files';

  protected Filesystem $fs;

  protected string $projectRootDir = '.';

  public function getProjectRootDir(): string {
    return $this->projectRootDir;
  }

  /**
   * Absolute path of the project root directory.
   *
   * Parent directory of the main composer.json file.
   */
  public function setProjectRootDir(string $projectRootDir): static {
    $this->projectRootDir = $projectRootDir;

    return $this;
  }

  protected string $buildDir = '';

  public function getBuildDir(): string {
    return $this->buildDir;
  }

  /**
   * Relative path from the project root directory to the build directory.
   *
   * Example: "artifacts/1.0.0/foo".
   */
  public function setBuildDir(string $directory): static {
    $this->buildDir = $directory;

    return $this;
  }

  /**
   * @var iterable<\Symfony\Component\Finder\SplFileInfo>
   */
  protected iterable $files = [];

  /**
   * @return iterable<\Symfony\Component\Finder\SplFileInfo>
   */
  public function getFiles(): iterable {
    return $this->files;
  }

  /**
   * @param iterable<\Symfony\Component\Finder\SplFileInfo> $files
   */
  public function setFiles(iterable $files): static {
    $this->files = $files;

    return $this;
  }

  public function __construct(
    ?Filesystem $fs = NULL,
  ) {
    $this->fs = $fs ?: new Filesystem();
  }

  /**
   * @phpstan-param marvin-robo-task-delete-artifact-files-options $options
   */
  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('projectRootDir', $options)) {
      $this->setProjectRootDir($options['projectRootDir']);
    }

    if (array_key_exists('buildDir', $options)) {
      $this->setBuildDir($options['buildDir']);
    }

    if (array_key_exists('files', $options)) {
      $this->setFiles($options['files']);
    }

    return $this;
  }

  protected function runAction(): static {
    foreach ($this->getFiles() as $file) {
      $this->runActionSingle($file);
    }

    return $this;
  }

  protected function runActionSingle(string|\SplFileInfo $file): static {
    $projectRootDir = $this->getProjectRootDir();
    $buildDir = $this->getBuildDir();
    $buildDirAbsolute = Path::join($projectRootDir, $buildDir);
    $filePathAbsolute = is_string($file)
      ? Path::join($projectRootDir, $buildDir, $file)
      : Path::join($projectRootDir, $file->getPathname());

    if (!str_starts_with($filePathAbsolute, $buildDirAbsolute)) {
      $this->printTaskWarning(
        "The file '{filePathAbsolute}' does not belong to the build directory {buildDirAbsolute}.",
        [
          'filePathAbsolute' => $filePathAbsolute,
          'buildDirAbsolute' => $buildDirAbsolute,
        ],
      );

      return $this;
    }

    $filePathRelativeFromBuildDir = preg_replace(
      '@^' . preg_quote("$buildDirAbsolute/", '@') . '@',
      '',
      $filePathAbsolute,
    );

    $filePathRelativeFromProjectRoot = Path::join($buildDir, $filePathRelativeFromBuildDir);

    $this->printTaskDebug(
      'Delete: {filePath}',
      [
        'filePath' => $filePathRelativeFromProjectRoot,
      ],
    );
    $this->fs->remove($filePathRelativeFromProjectRoot);

    return $this;
  }

}

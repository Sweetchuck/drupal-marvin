<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo\Task;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class CopyArtifactFilesTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected string $taskName = 'Marvin - Copy files';

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

  protected string $srcDir = '';

  public function getSrcDir(): string {
    return $this->srcDir;
  }

  public function setSrcDir(string $directory): static {
    $this->srcDir = $directory;

    return $this;
  }

  protected string $buildDir = '';

  public function getBuildDir(): string {
    return $this->buildDir;
  }

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
   * @phpstan-param marvin-robo-task-copy-files-options $options
   */
  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('projectRootDir', $options)) {
      $this->setProjectRootDir($options['projectRootDir']);
    }

    if (array_key_exists('srcDir', $options)) {
      $this->setSrcDir($options['srcDir']);
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

  /**
   * @param string|\Symfony\Component\Finder\SplFileInfo $file
   */
  protected function runActionSingle($file): static {
    $projectRootDir = $this->getProjectRootDir() ?: getcwd();
    $projectRootDir = realpath($projectRootDir);
    $srcDir = $this->getSrcDir();
    $srcDirAbsolute = Path::join($projectRootDir, $srcDir);
    $dstDir = $this->getBuildDir();
    $srcFilePathAbsolute = is_string($file)
      ? Path::join($projectRootDir, $srcDir, $file)
      : $file->getPathname();
    $srcFilePathRelative = preg_replace(
      '@^' . preg_quote("$srcDirAbsolute/", '@') . '@',
      '',
      $srcFilePathAbsolute,
    );

    $dstFilePathRelative = Path::join($dstDir, $srcFilePathRelative);

    $this->printTaskDebug(
      'copy: {srcDir} {dstDir} {file}',
      [
        'srcDir' => $srcDir,
        'dstDir' => $dstDir,
        'file' => $srcFilePathRelative,
      ]
    );
    $this->fs->copy($srcFilePathRelative, $dstFilePathRelative);

    return $this;
  }

}

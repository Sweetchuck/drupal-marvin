<?php

namespace Drush\marvin\Robo\Task;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class CopyFilesTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Copy files';

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * @var string
   */
  protected $srcDir = '';

  public function getSrcDir(): string {
    return $this->srcDir;
  }

  /**
   * @return $this
   */
  public function setSrcDir(string $directory) {
    $this->srcDir = $directory;

    return $this;
  }

  /**
   * @var string
   */
  protected $dstDir = '';

  public function getDstDir(): string {
    return $this->dstDir;
  }

  /**
   * @return $this
   */
  public function setDstDir(string $directory) {
    $this->dstDir = $directory;

    return $this;
  }

  /**
   * @var string[]|\Symfony\Component\Finder\SplFileInfo[]|\Symfony\Component\Finder\Finder
   */
  protected $files = NULL;

  /**
   * @return string[]|\Symfony\Component\Finder\Finder|\Symfony\Component\Finder\SplFileInfo[]
   */
  public function getFiles() {
    return $this->files;
  }

  /**
   * @param string[]|\Symfony\Component\Finder\Finder|\Symfony\Component\Finder\SplFileInfo[] $files
   *
   * @return $this
   */
  public function setFiles($files) {
    $this->files = $files;

    return $this;
  }

  public function __construct(array $options = [], Filesystem $fs = NULL) {
    parent::__construct($options);

    $this->fs = $fs ?: new Filesystem();
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    foreach ($options as $name => $value) {
      switch ($name) {
        case 'srcDir':
          $this->setSrcDir($value);
          break;

        case 'dstDir':
          $this->setDstDir($value);
          break;

        case 'files':
          $this->setFiles($value);
          break;

      }
    }

    return $this;
  }

  protected function runAction() {
    $srcDir = $this->getSrcDir();
    $dstDir = $this->getDstDir();

    /** @var string|\Symfony\Component\Finder\SplFileInfo $file */
    foreach ($this->getFiles() as $file) {
      $isString = is_string($file);
      $relativeFileName = $isString ? $file : $file->getRelativePathname();
      $srcFileName = $isString ? Path::join($srcDir, $file) : $file->getPathname();
      $dstFileName = Path::join($dstDir, $relativeFileName);

      $this->printTaskDebug(
        "COPY FILE: {srcDir} {file} {dstDir}",
        [
          'srcDir' => $srcDir,
          'file' => $relativeFileName,
          'dstDir' => $dstDir,
        ]
      );
      $this->fs->copy($srcFileName, $dstFileName);
    }

    return $this;
  }

}

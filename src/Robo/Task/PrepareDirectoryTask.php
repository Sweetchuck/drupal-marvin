<?php

namespace Drush\marvin\Robo\Task;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class PrepareDirectoryTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Prepare directory';

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

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
  public function __construct(array $options = [], Filesystem $fs = NULL) {
    parent::__construct($options);

    $this->fs = $fs ?: new Filesystem();
  }

  public function setOptions(array $options) {
    parent::setOptions($options);
    foreach ($options as $name => $value) {
      switch ($name) {
        case 'workingDirectory':
          $this->setWorkingDirectory($value);
          break;

      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $dir = $this->getWorkingDirectory();
    if (!$this->fs->exists($dir)) {
      $this->fs->mkdir($dir);
    }
    else {
      $directDescendants = (new Finder())
        ->in($dir)
        ->depth('== 0')
        ->ignoreDotFiles(TRUE);
      $this->fs->remove($directDescendants);
    }

    return $this;
  }

}

<?php

namespace Drupal\marvin\Robo\Task;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Creates an empty directory with the given name.
 *
 * If the given directory isn't exists then creates it, otherwise deletes
 * everything in that directory.
 *
 * @todo Move this task out into an individual package.
 */
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
  public function __construct(Filesystem $fs = NULL) {
    $this->fs = $fs ?: new Filesystem();
  }

  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('workingDirectory', $options)) {
      $this->setWorkingDirectory($options['workingDirectory']);
    }

    return $this;
  }

  protected function runHeader() {
    $this->printTaskInfo(
      '{workingDirectory}',
      [
        'workingDirectory' => $this->getWorkingDirectory(),
      ]
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $dir = $this->getWorkingDirectory();
    $context = [
      'workingDirectory' => $dir,
    ];

    if (!$this->fs->exists($dir)) {
      $this->printTaskDebug('Create directory: {workingDirectory}', $context);
      $this->fs->mkdir($dir);

      return $this;
    }

    $this->printTaskDebug('Delete all content from directory "{workingDirectory}"', $context);
    $this->fs->remove($this->getDirectDescendants($dir));

    return $this;
  }

  protected function getDirectDescendants(string $dir): Finder {
    return (new Finder())
      ->in($dir)
      ->depth('== 0')
      ->ignoreDotFiles(TRUE);
  }

}

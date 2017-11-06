<?php

namespace Drush\marvin\ArrayUtils;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class FileSystemArrayUtils {

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * @var string
   */
  public $baseDir = '.';

  public function __construct(?Filesystem $fs = NULL, array $options = []) {
    $this->fs = $fs ?: new Filesystem();

    foreach ($options as $key => $value) {
      switch ($key) {
        case 'baseDir':
          $this->baseDir = $value;
          break;

      }
    }
  }

  /**
   * Returns FALSE if $filePath doesn't contains "*" and not exists.
   */
  public function filterNonExistsPaths(string $filePath): bool {
    $filePath = Path::join($this->baseDir, $filePath);

    return (mb_strpos($filePath, '*') || $this->fs->exists($filePath));
  }

  public function walkExists(bool &$exists, string $filePath) {
    if (mb_strpos($filePath, '*')) {
      return;
    }

    $exists = $this->fs->exists(Path::join($this->baseDir, $filePath));
  }

}

<?php

namespace Drush\Commands\Marvin\Tests\Unit\ArrayUtils;

use Drush\marvin\ArrayUtils\FileSystemArrayUtils;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class FileSystemArrayUtilsTest extends TestCase {

  /**
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $rootDir;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->rootDir = vfsStream::setup('ComposerInfo');
  }

  protected function tearDown() {
    parent::tearDown();

    (new Filesystem())->remove($this->rootDir->getName());
  }

  public function casesWalkExists(): array {
    return [
      'empty' => [
        [],
        [],
        [],
      ],
      'basic' => [
        [
          'a.txt' => TRUE,
          'b.txt' => FALSE,
          'c.txt' => TRUE,
          'd.txt' => FALSE,
          '*.js' => TRUE,
          '*.css' => FALSE,
        ],
        [
          'a.txt' => FALSE,
          'b.txt' => FALSE,
          'c.txt' => FALSE,
          'd.txt' => FALSE,
          '*.js' => TRUE,
          '*.css' => FALSE,
        ],
        [
          'a.txt',
          'c.txt',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesWalkExists
   */
  public function testWalkExists(array $expected, array $items, array $filesToCreate): void {
    foreach ($filesToCreate as $fileName) {
      file_put_contents($this->rootDir->url() . "/$fileName", 'abc');
    }

    $filter = new FileSystemArrayUtils();
    $filter->baseDir = $this->rootDir->url();
    array_walk($items, [$filter, 'walkExists']);

    $this->assertEquals($expected, $items);
  }

}

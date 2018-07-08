<?php

namespace Drupal\marvin\Tests\Unit;

use Drupal\marvin\ComposerInfo;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @coversDefaultClass \Drupal\marvin\ComposerInfo<extends>
 */
class ComposerInfoTest extends TestCase {

  /**
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $rootDir;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->fs = new Filesystem();

    $this->rootDir = vfsStream::setup('ComposerInfo');
  }

  protected function tearDown() {
    $this->fs->remove($this->rootDir->getName());
    $this->rootDir = NULL;

    parent::tearDown();
  }

  public function casesGetLockFileName(): array {
    return [
      'empty' => [
        '/ComposerInfo/composer.lock',
        '',
      ],
      'basic' => [
        '/ComposerInfo/composer.lock',
        'composer.json',
      ],
      'advanced' => [
        '/ComposerInfo/a/b/c.lock',
        'a/b/c.json',
      ],
    ];
  }

  /**
   * @dataProvider casesGetLockFileName
   */
  public function testGetLockFileName(string $expected, string $jsonFileName) {
    $baseDir = $this->rootDir->url();
    $ci = ComposerInfo::create($baseDir, $jsonFileName);
    $this->assertEquals(
      "vfs:/$expected",
      $ci->getLockFileName()
    );
  }

  public function casesGetWorkingDirectory(): array {
    return [
      'empty' => [
        '/ComposerInfo',
        '',
      ],
      'basic' => [
        '/ComposerInfo',
        'composer.json',
      ],
      'advanced' => [
        '/ComposerInfo/a/b',
        'a/b/c.json',
      ],
    ];
  }

  /**
   * @dataProvider casesGetWorkingDirectory
   */
  public function testGetWorkingDirectory(string $expected, string $jsonFileName) {
    $baseDir = $this->rootDir->url();
    $ci = ComposerInfo::create($baseDir, $jsonFileName);
    $this->assertEquals("vfs:/$expected", $ci->getWorkingDirectory());
  }

  public function casesCreate(): array {
    return [
      'basic' => [
        [
          'json' => [
            'name' => 'aa/bb',
            'type' => 'library',
            'config' => [
              'bin-dir' => 'vendor/bin',
              'vendor-dir' => 'vendor',
            ],

          ],
          'lock' => [
            'packages' => [
              'a/b' => [
                'name' => 'a/b',
              ],
              'c/d' => [
                'name' => 'c/d',
              ],
            ],
            'packages-dev' => [
              'e/f' => [
                'name' => 'e/f',
              ],
              'g/h' => [
                'name' => 'g/h',
              ],
            ],
          ],
        ],
        [
          'name' => 'aa/bb',
        ],
        [
          'packages' => [
            [
              'name' => 'a/b',
            ],
            [
              'name' => 'c/d',
            ],
          ],
          'packages-dev' => [
            [
              'name' => 'e/f',
            ],
            [
              'name' => 'g/h',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesCreate
   */
  public function testCreate(array $expected, array $json, array $lock): void {
    $baseDir = $this->rootDir->url();
    mkdir("$baseDir/real");

    $jsonFileName = 'real/composer.json';
    $this->fs->dumpFile("$baseDir/$jsonFileName", json_encode($json));

    $lockFileName = 'real/composer.lock';
    $this->fs->dumpFile("$baseDir/$lockFileName", json_encode($lock));

    $ci = ComposerInfo::create($baseDir, $jsonFileName);
    $this->assertEquals($expected['json'], $ci->getJson());
    $this->assertEquals($expected['lock'], $ci->getLock());
  }

  public function testInstances() {
    $vfs = vfsStream::setup(
      'instances',
      NULL,
      [
        'p1' => [
          'composer.json' => json_encode(['type' => 'a']),
        ],
        'p2' => [
          'composer.json' => json_encode(['type' => 'b']),
        ],
      ]
    );

    $p1 = ComposerInfo::create($vfs->url() . '/p1');
    $p2 = ComposerInfo::create($vfs->url() . '/p2');
    $this->assertEquals('a', $p1['type']);
    $this->assertEquals('b', $p2['type']);
  }

}

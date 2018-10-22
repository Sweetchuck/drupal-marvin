<?php

namespace Drupal\marvin\Tests\Unit;

use Drupal\marvin\ComposerInfo;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Error\Error as PHPUnitFrameworkError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * @covers \Drupal\marvin\ComposerInfo<extended>
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
  public function testGetLockFileName(string $expected, string $jsonFileName): void {
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
  public function testGetWorkingDirectory(string $expected, string $jsonFileName): void {
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
      'without lock' => [
        [
          'json' => [
            'name' => 'aa/bb',
            'type' => 'library',
            'config' => [
              'bin-dir' => 'vendor/bin',
              'vendor-dir' => 'vendor',
            ],
          ],
          'lock' => [],
        ],
        [
          'name' => 'aa/bb',
        ],
        NULL,
      ],
    ];
  }

  /**
   * @dataProvider casesCreate
   */
  public function testCreate(array $expected, array $json, ?array $lock): void {
    $baseDir = Path::join($this->rootDir->url(), __FUNCTION__, $this->dataName());
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile("$baseDir/$baseName.json", json_encode($json));
    if ($lock !== NULL) {
      $this->fs->dumpFile("$baseDir/$baseName.lock", json_encode($lock));
    }

    $ci = ComposerInfo::create($baseDir, "$baseName.json");
    $this->assertEquals($expected['json'], $ci->getJson());
    $this->assertEquals($expected['lock'], $ci->getLock());
  }

  public function testInstances(): void {
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

  public function casesGetDrupalExtensionInstallDir(): array {
    return [
      'empty' => [
        NULL,
        'module',
        [],
      ],
      'basic' => [
        'web/modules/contrib/{name}',
        'module',
        [
          'extra' => [
            'installer-paths' => [
              'web/modules/contrib/{name}' => ['type:drupal-module'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetDrupalExtensionInstallDir
   */
  public function testGetDrupalExtensionInstallDir(?string $expected, string $type, array $json): void {
    $baseDir = $this->rootDir->url() . '/' . __FUNCTION__ . '/' . $this->dataName();
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile("$baseDir/$baseName.json", json_encode($json));

    $ci = ComposerInfo::create($baseDir, "$baseName.json");
    $this->assertEquals($expected, $ci->getDrupalExtensionInstallDir($type));
  }

  public function testOffsetUnset(): void {
    $json = [
      'name' => 'a/b',
    ];

    $baseDir = Path::join($this->rootDir->url(), __FUNCTION__, $this->dataName());
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile("$baseDir/$baseName.json", json_encode($json));
    $ci = ComposerInfo::create($baseDir, "$baseName.json");
    $this->assertSame('a/b', $ci['name']);
    unset($ci['name']);
    $this->assertNull($ci->name);
  }

  public function testMagicGet(): void {
    $json = [];

    $baseDir = Path::join($this->rootDir->url(), __FUNCTION__, $this->dataName());
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile("$baseDir/$baseName.json", json_encode($json));
    $ci = ComposerInfo::create($baseDir, "$baseName.json");

    $this->assertFalse(isset($ci['name']));

    $this->assertSame(NULL, $ci->name);
    $this->assertSame(NULL, $ci->packageVendor);
    $this->assertSame(NULL, $ci->packageName);

    $json['name'] = 'c/d';
    $this->fs->dumpFile("$baseDir/$baseName.json", json_encode($json));
    $this->assertSame(NULL, $ci->name);
    $ci->invalidate();
    $this->assertSame('c/d', $ci->name);
    $this->assertSame('c', $ci->packageVendor);
    $this->assertSame('d', $ci->packageName);

    $ci['name'] = 'e/f';
    $this->assertSame('e/f', $ci->name);
    $this->assertSame('e', $ci->packageVendor);
    $this->assertSame('f', $ci->packageName);
  }

  public function testMagicGetUnknown(): void {
    $json = [
      'name' => 'a/b',
    ];

    $baseDir = Path::join($this->rootDir->url(), __FUNCTION__, $this->dataName());
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile("$baseDir/$baseName.json", json_encode($json));
    $ci = ComposerInfo::create($baseDir, "$baseName.json");

    $this->expectException(PHPUnitFrameworkError::class);
    $this->expectExceptionCode(E_USER_NOTICE);
    $this->assertNull($ci->{'notExists'});
  }

  public function testCheckJsonExists(): void {
    $baseDir = Path::join($this->rootDir->url(), __FUNCTION__, $this->dataName());
    $ci = ComposerInfo::create($baseDir, "not-exists.json");
    $this->expectException(FileNotFoundException::class);
    $this->expectExceptionCode(1);
    $ci->getJson();
  }

}

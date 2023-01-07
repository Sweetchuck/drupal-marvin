<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit;

use Drupal\marvin\ComposerInfo;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @group marvin
 *
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
  public function setUp(): void {
    parent::setUp();
    $this->fs = new Filesystem();

    $this->rootDir = vfsStream::setup('ComposerInfo');
  }

  protected function tearDown(): void {
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
    static::assertSame(
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
    static::assertSame("vfs:/$expected", $ci->getWorkingDirectory());
  }

  public function casesCreate(): array {
    return [
      'basic' => [
        [
          'json' => [
            'type' => 'library',
            'config' => [
              'bin-dir' => 'vendor/bin',
              'vendor-dir' => 'vendor',
            ],
            'name' => 'aa/bb',
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
            'type' => 'library',
            'config' => [
              'bin-dir' => 'vendor/bin',
              'vendor-dir' => 'vendor',
            ],
            'name' => 'aa/bb',
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
    static::assertSame($expected['json'], $ci->getJson());
    static::assertSame($expected['lock'], $ci->getLock());
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

    $project1 = ComposerInfo::create($vfs->url() . '/p1');
    $project2 = ComposerInfo::create($vfs->url() . '/p2');
    static::assertSame('a', $project1['type']);
    static::assertSame('b', $project2['type']);
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
    static::assertSame($expected, $ci->getDrupalExtensionInstallDir($type));
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
    static::assertSame('a/b', $ci['name']);
    unset($ci['name']);
    static::assertNull($ci->name);
  }

  public function testMagicGet(): void {
    $json = [];

    $baseDir = Path::join($this->rootDir->url(), __FUNCTION__, $this->dataName());
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile("$baseDir/$baseName.json", json_encode($json));
    $ci = ComposerInfo::create($baseDir, "$baseName.json");

    static::assertFalse(isset($ci['name']));

    static::assertSame(NULL, $ci->name);
    static::assertSame(NULL, $ci->packageVendor);
    static::assertSame(NULL, $ci->packageName);

    $json['name'] = 'c/d';
    $this->fs->dumpFile("$baseDir/$baseName.json", json_encode($json));
    static::assertSame(NULL, $ci->name);
    $ci->invalidate();
    static::assertSame('c/d', $ci->name);
    static::assertSame('c', $ci->packageVendor);
    static::assertSame('d', $ci->packageName);

    $ci['name'] = 'e/f';
    static::assertSame('e/f', $ci->name);
    static::assertSame('e', $ci->packageVendor);
    static::assertSame('f', $ci->packageName);
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

    static::expectError();
    static::expectExceptionCode(E_USER_NOTICE);
    static::assertNull($ci->{'notExists'});
  }

  public function testCheckJsonExists(): void {
    $baseDir = Path::join($this->rootDir->url(), __FUNCTION__, $this->dataName());
    $ci = ComposerInfo::create($baseDir, "not-exists.json");
    static::expectException(FileNotFoundException::class);
    static::expectExceptionCode(1);
    $ci->getJson();
  }

}

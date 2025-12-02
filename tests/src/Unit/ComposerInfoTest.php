<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit;

use Drupal\marvin\ComposerInfo;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[Group('marvin')]
#[CoversClass(ComposerInfo::class)]
class ComposerInfoTest extends TestCase {

  protected ?vfsStreamDirectory $rootDir;

  protected Filesystem $fs;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function setUp(): void {
    parent::setUp();
    $this->fs = new Filesystem();

    $this->rootDir = vfsStream::setup('ComposerInfo');
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function tearDown(): void {
    $this->fs->remove($this->rootDir->getName());
    $this->rootDir = NULL;

    parent::tearDown();
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetLockFileName(): array {
    return [
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

  #[Test]
  #[DataProvider('casesGetLockFileName')]
  public function testGetLockFileName(string $expected, string $jsonFileName): void {
    $baseDir = $this->rootDir->url();
    $ci = ComposerInfo::create($baseDir, $jsonFileName);
    static::assertSame(
      "vfs:/$expected",
      $ci->getLockFilePath(),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetWorkingDirectory(): array {
    return [
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

  #[Test]
  #[DataProvider('casesGetWorkingDirectory')]
  public function testGetWorkingDirectory(string $expected, string $jsonFileName): void {
    $baseDir = $this->rootDir->url();
    $ci = ComposerInfo::create($baseDir, $jsonFileName);
    static::assertSame("vfs:/$expected", $ci->getWorkingDirectory());
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesCreate(): array {
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
          'lock' => [
            'content-hash' => '',
            'packages' => [],
            'packages-dev' => [],
            'aliases' => [],
            'minimum-stability' => [],
            'stability-flags' => [],
            'prefer-stable' => TRUE,
            'prefer-lowest' => FALSE,
            'platform' => [],
            'platform-dev' => [],
            'plugin-api-version' => '',
          ],
        ],
        [
          'name' => 'aa/bb',
        ],
        NULL,
      ],
    ];
  }

  /**
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param array<string, mixed> $json
   * @phpstan-param null|array<string, mixed> $lock
   */
  #[Test]
  #[DataProvider('casesCreate')]
  public function testCreate(array $expected, array $json, ?array $lock): void {
    $baseDir = Path::join(
      $this->rootDir->url(),
      __FUNCTION__,
      (string) $this->dataName(),
    );
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile(
      "$baseDir/$baseName.json",
      json_encode($json) ?: '{}',
    );
    if ($lock !== NULL) {
      $this->fs->dumpFile(
        "$baseDir/$baseName.lock",
        json_encode($lock) ?: '{}',
      );
    }

    $ci = ComposerInfo::create($baseDir, "$baseName.json");
    static::assertSame($expected['json'], $ci->getJson());
    static::assertSame($expected['lock'], $ci->getLock());
  }

  #[Test]
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

    $project1 = ComposerInfo::create($vfs->url() . '/p1', 'composer.json');
    $project2 = ComposerInfo::create($vfs->url() . '/p2', 'composer.json');
    static::assertSame('a', $project1['type']);
    static::assertSame('b', $project2['type']);
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetDrupalExtensionInstallDirPattern(): array {
    return [
      'empty' => [
        'vendor/{$vendor}/{$name}',
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
   * @phpstan-param array<string, mixed> $json
   */
  #[DataProvider('casesGetDrupalExtensionInstallDirPattern')]
  public function testGetDrupalExtensionInstallDirPattern(?string $expected, string $type, array $json): void {
    $baseDir = $this->rootDir->url() . '/' . __FUNCTION__ . '/' . $this->dataName();
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile(
      "$baseDir/$baseName.json",
      json_encode($json) ?: '{}',
    );

    $ci = ComposerInfo::create($baseDir, "$baseName.json");
    static::assertSame($expected, $ci->getDrupalExtensionInstallDirPattern($type));
  }

  #[Test]
  public function testGetDrupalExtensionInstallDirPatterns(): void {
    $expected = [
      'core' => 'vendor/{$vendor}/{$name}',
      'drupal-core' => 'vendor/{$vendor}/{$name}',
      'module' => 'vendor/{$vendor}/{$name}',
      'drupal-module' => 'vendor/{$vendor}/{$name}',
      'theme' => 'vendor/{$vendor}/{$name}',
      'drupal-theme' => 'vendor/{$vendor}/{$name}',
      'profile' => 'vendor/{$vendor}/{$name}',
      'drupal-profile' => 'vendor/{$vendor}/{$name}',
      'engine' => 'vendor/{$vendor}/{$name}',
      'drupal-engine' => 'vendor/{$vendor}/{$name}',
      'recipe' => 'vendor/{$vendor}/{$name}',
      'drupal-recipe' => 'vendor/{$vendor}/{$name}',
      'drush' => 'vendor/{$vendor}/{$name}',
      'drupal-drush' => 'vendor/{$vendor}/{$name}',
    ];
    $json = [];
    $baseDir = $this->rootDir->url() . '/' . __FUNCTION__ . '/' . $this->dataName();
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile(
      "$baseDir/$baseName.json",
      json_encode($json) ?: '{}',
    );

    $ci = ComposerInfo::create($baseDir, "$baseName.json");
    static::assertSame($expected, $ci->getDrupalExtensionInstallDirPatterns());
  }

  #[Test]
  public function testOffsetUnset(): void {
    $json = [
      'name' => 'a/b',
    ];

    $baseDir = Path::join(
      $this->rootDir->url(),
      __FUNCTION__,
      (string) $this->dataName(),
    );
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile(
      "$baseDir/$baseName.json",
      json_encode($json) ?: '{}',
    );
    $ci = ComposerInfo::create($baseDir, "$baseName.json");
    static::assertSame('a/b', $ci['name']);
    unset($ci['name']);
    static::assertNull($ci->name);
  }

  #[Test]
  public function testMagicGet(): void {
    $json = [];

    $baseDir = Path::join(
      $this->rootDir->url(),
      __FUNCTION__,
      (string) $this->dataName(),
    );
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile(
      "$baseDir/$baseName.json",
      json_encode($json) ?: '{}',
    );
    $ci = ComposerInfo::create($baseDir, "$baseName.json");

    static::assertFalse(isset($ci['name']));

    static::assertSame(NULL, $ci->name);
    static::assertSame(NULL, $ci->packageVendor);
    static::assertSame(NULL, $ci->packageName);

    $json['name'] = 'c/d';
    $this->fs->dumpFile(
      "$baseDir/$baseName.json",
      json_encode($json) ?: '{}',
    );
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

  #[Test]
  public function testMagicGetUnknown(): void {
    $json = [
      'name' => 'a/b',
    ];

    $baseDir = Path::join(
      $this->rootDir->url(),
      __FUNCTION__,
      (string) $this->dataName(),
    );
    mkdir($baseDir);

    $baseName = 'composer';
    $this->fs->dumpFile(
      "$baseDir/$baseName.json",
      json_encode($json) ?: '{}',
    );
    $ci = ComposerInfo::create($baseDir, "$baseName.json");

    static::expectException(\ValueError::class);
    static::expectExceptionCode(0);
    /** @phpstan-ignore-next-line */
    static::assertNull($ci->{'notExists'});
  }

  #[Test]
  public function testCheckJsonExists(): void {
    $baseDir = Path::join(
      $this->rootDir->url(),
      __FUNCTION__,
      (string) $this->dataName(),
    );
    $ci = ComposerInfo::create($baseDir, 'not-exists.json');
    static::expectException(FileNotFoundException::class);
    static::expectExceptionCode(1);
    $ci->getJson();
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesDetectDrupalRootDir(): array {
    return [
      'empty' => [
        'vendor/drupal',
        [],
      ],
      'type:drupal-core' => [
        'a/b',
        [
          'extra' => [
            'installer-paths' => [
              'a/b/core' => ['type:drupal-core'],
            ],
          ],
        ],
      ],
      'name:drupal/core' => [
        'a/b',
        [
          'extra' => [
            'installer-paths' => [
              'a/b/core' => ['drupal/core'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @phpstan-param array<string, mixed> $json
   */
  #[Test]
  #[DataProvider('casesDetectDrupalRootDir')]
  public function testDetectDrupalRootDir(string $expected, array $json): void {
    $vfsStructure = [
      'composer.json' => json_encode($json),
    ];

    $vfs = vfsStream::setup(
      __FUNCTION__ . '.' . $this->dataName(),
      NULL,
      $vfsStructure
    );
    $composerInfo = ComposerInfo::create($vfs->url(), 'composer.json');
    static::assertSame(
      $expected,
      $composerInfo->getDrupalRootDir(),
    );
  }

}

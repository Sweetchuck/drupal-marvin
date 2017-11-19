<?php

namespace Drush\marvin\Tests\Unit;

use Drush\marvin\Utils;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Webmozart\PathUtil\Path;

/**
 * @coversDefaultClass \Drush\marvin\Utils
 */
class UtilsTest extends TestCase {

  public function casesCommandClassNameToConfigIdentifier(): array {
    return [
      'with leading backslash - Qa\Lint*' => [
        'marvin.qa.lint.phpcs',
        '\Drush\Commands\Marvin\Qa\LintPhpcsCommands',
      ],
      'without leading backslash - Qa\Lint*' => [
        'marvin.qa.lint.phpcs',
        'Drush\Commands\Marvin\Qa\LintPhpcsCommands',
      ],
      'without leading backslash - Qa\Phpunit' => [
        'marvin.qa.phpunit',
        'Drush\Commands\Marvin\Qa\PhpunitCommands',
      ],
    ];
  }

  /**
   * @covers ::commandClassNameToConfigIdentifier
   *
   * @dataProvider casesCommandClassNameToConfigIdentifier
   */
  public function testCommandClassNameToConfigIdentifier(string $expected, string $className): void {
    $this->assertEquals(
      $expected,
      Utils::commandClassNameToConfigIdentifier($className)
    );
  }

  public function casesCollectManagedDrupalExtensions(): array {
    return [
      'empty' => [
        [],
        '/a/b',
        [],
        [],
      ],
      'basic' => [
        [
          'v1/a' => '/a/c',
          'v2/a' => '/a/c/c',
        ],
        '/a/b',
        [
          'packages' => [
            'v1/a' => [
              'type' => 'drupal-module',
            ],
            'v2/a' => [
              'type' => 'drupal-drush',
            ],
          ],
        ],
        [
          'v1/a' => '/a/c',
          'v1/b' => '/a/b',
          'v1/c' => '/a/b/c',
          'v2/a' => '/a/c/c',
        ],
      ],
    ];
  }

  /**
   * @covers ::collectManagedDrupalExtensions
   *
   * @dataProvider casesCollectManagedDrupalExtensions
   */
  public function testCollectManagedDrupalExtensions(
    array $expected,
    string $rootProjectDir,
    array $composerLock,
    array $packagePaths
  ): void {
    $this->assertEquals(
      $expected,
      Utils::collectManagedDrupalExtensions($rootProjectDir, $composerLock, $packagePaths)
    );
  }

  public function casesFindFileUpward(): array {
    return [
      'not-exists' => [
        '',
        'a.txt',
        'foo',
        [
          'foo' => [],
        ],
      ],
      '0-0' => [
        'vfs://testFindFileUpward',
        'a.txt',
        '.',
        [
          'a.txt' => 'okay',
        ],
      ],
      '1-0' => [
        'vfs://testFindFileUpward',
        'a.txt',
        'foo',
        [
          'a.txt' => 'okay',
          'foo' => [],
        ],
      ],
      '2-0' => [
        'vfs://testFindFileUpward',
        'a.txt',
        'foo/bar',
        [
          'a.txt' => 'okay',
          'foo' => [
            'bar' => [],
          ],
        ],
      ],
      '2-1' => [
        'vfs://testFindFileUpward/foo',
        'a.txt',
        'foo/bar',
        [
          'foo' => [
            'bar' => [],
            'a.txt' => 'okay',
          ],
        ],
      ],
      '2-2' => [
        'vfs://testFindFileUpward/foo/bar',
        'a.txt',
        'foo/bar',
        [
          'foo' => [
            'bar' => [
              'a.txt' => 'okay',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesFindFileUpward
   */
  public function testFindFileUpward($expected, string $fileName, string $relativeDirectory, array $structure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $structure);
    $absoluteDirectory = Path::join($vfs->url(), $relativeDirectory);

    $this->assertEquals($expected, Utils::findFileUpward($fileName, $absoluteDirectory));
  }

}

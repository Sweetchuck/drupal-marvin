<?php

namespace Drupal\Tests\marvin\Unit;

use Drupal\marvin\Utils;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Webmozart\PathUtil\Path;

/**
 * @coversDefaultClass \Drupal\marvin\Utils
 */
class UtilsTest extends TestCase {

  public function casesCommandClassNameToConfigIdentifier(): array {
    return [
      'with leading backslash - Lint*' => [
        'marvin.lint.phpcs',
        '\Drush\Commands\Marvin\Lint\LintPhpcsCommands',
      ],
      'without leading backslash - Lint*' => [
        'marvin.lint.phpcs',
        'Drush\Commands\Marvin\Lint\LintPhpcsCommands',
      ],
      'without leading backslash - Phpunit' => [
        'marvin.test.phpunit',
        'Drush\Commands\Marvin\Test\PhpunitCommands',
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
  public function testFindFileUpward($expected, string $fileName, string $relativeDirectory, array $vfsStructure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $absoluteDirectory = Path::join($vfs->url(), $relativeDirectory);

    $this->assertEquals($expected, Utils::findFileUpward($fileName, $absoluteDirectory));
  }

  public function casesGetDirectDescendantDrupalPhpFiles(): array {
    return [
      'empty' => [
        [],
        '',
        [],
      ],
      'basic' => [
        [
          'a.engine',
          'a.install',
          'a.module',
          'a.profile',
          'a.theme',
          'a.php',
        ],
        'a',
        [
          'a' => [
            'a.engine' => '',
            'a.install' => '',
            'a.module' => '',
            'a.profile' => '',
            'a.theme' => '',
            'a.php' => '',
            'a.inc' => '',
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetDirectDescendantDrupalPhpFiles
   */
  public function testGetDirectDescendantDrupalPhpFiles(array $expected, string $relativeDirectory, array $vfsStructure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $absoluteDirectory = Path::join($vfs->url(), $relativeDirectory);

    $this->assertSame(
      $expected,
      Utils::getDirectDescendantDrupalPhpFiles($absoluteDirectory)
    );
  }

  public function casesPhpUnitSuiteNameToNamespace(): array {
    return [
      'unit' => ['Unit', 'unit'],
      'kernel' => ['Kernel', 'kernel'],
      'functional' => ['Functional', 'functional'],
      'functional-javascript' => ['FunctionalJavascript', 'functional-javascript'],
    ];
  }

  /**
   * @dataProvider casesPhpUnitSuiteNameToNamespace
   */
  public function testPhpUnitSuiteNameToNamespace($expected, string $suiteName): void {
    $this->assertSame($expected, Utils::phpUnitSuiteNameToNamespace($suiteName));
  }

  public function casesParseDrupalExtensionVersionNumber(): array {
    return [
      'minimum' => [
        [
          'coreMajor' => 8,
          'extensionMajor' => 1,
          'extensionMinor' => 2,
          'extensionPreType' => '',
          'extensionPreMajor' => 0,
          'extensionBuild' => '',
        ],
        '8.x-1.2',
      ],
      'with pre' => [
        [
          'coreMajor' => 8,
          'extensionMajor' => 1,
          'extensionMinor' => 2,
          'extensionPreType' => 'beta',
          'extensionPreMajor' => 3,
          'extensionBuild' => '',
        ],
        '8.x-1.2-beta3',
      ],
      'with build' => [
        [
          'coreMajor' => 8,
          'extensionMajor' => 1,
          'extensionMinor' => 2,
          'extensionPreType' => '',
          'extensionPreMajor' => 0,
          'extensionBuild' => 'foo.bar',
        ],
        '8.x-1.2+foo.bar',
      ],
      'full' => [
        [
          'coreMajor' => 8,
          'extensionMajor' => 1,
          'extensionMinor' => 2,
          'extensionPreType' => 'beta',
          'extensionPreMajor' => 3,
          'extensionBuild' => 'foo.bar',
        ],
        '8.x-1.2-beta3+foo.bar',
      ],
    ];
  }

  /**
   * @dataProvider casesParseDrupalExtensionVersionNumber
   */
  public function testParseDrupalExtensionVersionNumber($expected, $versionNumber): void {
    $this->assertEquals($expected, Utils::parseDrupalExtensionVersionNumber($versionNumber));
  }

  public function casesDbUrl(): array {
    return [
      'sqlite - basic' => [
        'sqlite:///default__default.sqlite',
        [
          'driver' => 'sqlite',
          'database' => '/default__default.sqlite',
        ],
      ],
      'mysql - full' => [
        'mysql://a:b@c:d/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'password' => 'b',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-port;' => [
        'mysql://a:b@c/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'password' => 'b',
          'host' => 'c',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-password;' => [
        'mysql://a@c:d/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-prefix;' => [
        'mysql://a@c:d/e',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
        ],
      ],
      'mysql - no-all;' => [
        'mysql://c/e',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
        ],
      ],
      'mysql - prefix array default;' => [
        'mysql://c/e#f',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
          'prefix' => ['default' => 'f'],
        ],
      ],
      'mysql - prefix array no-default;' => [
        'mysql://c/e',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
          'prefix' => ['foo' => 'f'],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesDbUrl
   */
  public function testDbUrl($expected, array $connection): void {
    $this->assertSame($expected, Utils::dbUrl($connection));
  }

}

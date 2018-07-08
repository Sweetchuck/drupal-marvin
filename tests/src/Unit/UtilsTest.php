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

  public function casesCollectManagedDrupalExtensions(): array {
    $vfsRoot = 'vfs://testCollectManagedDrupalExtensions';

    return [
      'empty' => [
        [],
        "$vfsRoot/dir/inside",
        [],
        [],
        [],
      ],
      'basic' => [
        [
          'v1/profile_01_outside_git' => "$vfsRoot/dir/outside/v1/profile_01",
          'v1/module_01_outside_git' => "$vfsRoot/dir/outside/v1/module_01",
          'v1/theme_01_outside_git' => "$vfsRoot/dir/outside/v1/theme_01",
          'v1/drush_01_outside_git' => "$vfsRoot/dir/outside/v1/drush_01",
        ],
        "$vfsRoot/dir/inside",
        [
          'packages' => [
            'v1/profile_01_outside_git' => [
              'type' => 'drupal-profile',
            ],
            'v1/module_01_outside_git' => [
              'type' => 'drupal-module',
            ],
            'v1/theme_01_outside_git' => [
              'type' => 'drupal-theme',
            ],
            'v1/drush_01_outside_git' => [
              'type' => 'drupal-drush',
            ],
            'v1/library_01_outside_git' => [
              'type' => 'library',
            ],
            'v1/module_02_inside_git' => [
              'type' => 'drupal-module',
            ],
            'v1/module_03_outside_zip' => [
              'type' => 'drupal-module',
            ],
          ],
        ],
        [
          'v1/profile_01_outside_git' => "$vfsRoot/dir/outside/v1/profile_01",
          'v1/module_01_outside_git' => "$vfsRoot/dir/outside/v1/module_01",
          'v1/theme_01_outside_git' => "$vfsRoot/dir/outside/v1/theme_01",
          'v1/drush_01_outside_git' => "$vfsRoot/dir/outside/v1/drush_01",
          'v1/library_01_outside_git' => "$vfsRoot/dir/outside/v1/library_01",
          'v1/module_02_inside_git' => "$vfsRoot/dir/inside/modules/module_02",
          'v1/module_03_outside_zip' => "$vfsRoot/dir/outside/modules/module_03",
        ],
        [
          'dir' => [
            'inside' => [
              'modules' => [
                'module_02' => [
                  '.git' => [],
                ],
              ],
            ],
            'outside' => [
              'v1' => [
                'profile_01' => [
                  '.git' => [],
                ],
                'module_01' => [
                  '.git' => [],
                ],
                'theme_01' => [
                  '.git' => [],
                ],
                'drush_01' => [
                  '.git' => [],
                ],
                'library_01' => [
                  '.git' => [],
                ],
                'module_03' => [],
              ],
            ],
          ],
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
    array $packagePaths,
    array $vfsStructure
  ): void {
    vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);

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
  public function testFindFileUpward($expected, string $fileName, string $relativeDirectory, array $vfsStructure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $absoluteDirectory = Path::join($vfs->url(), $relativeDirectory);

    $this->assertEquals($expected, Utils::findFileUpward($fileName, $absoluteDirectory));
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

}

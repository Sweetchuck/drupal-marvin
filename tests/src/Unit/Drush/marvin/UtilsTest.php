<?php

namespace Drush\marvin\Tests\Unit;

use Drush\marvin\Utils;
use PHPUnit\Framework\TestCase;

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

}

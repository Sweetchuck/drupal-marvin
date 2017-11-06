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
      'with leading backslash' => [
        'marvin.qa.lint.phpcs',
        '\Drush\Commands\Marvin\Qa\LintPhpcsCommands',
      ],
      'without leading backslash' => [
        'marvin.qa.lint.phpcs',
        'Drush\Commands\Marvin\Qa\LintPhpcsCommands',
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

}

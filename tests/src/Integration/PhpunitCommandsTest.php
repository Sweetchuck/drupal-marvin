<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\PhpunitCommandsBase<extended>
 * @covers \Drupal\marvin\CommandDelegatorTrait
 * @covers \Drupal\marvin\PhpVariantTrait
 */
class PhpunitCommandsTest extends UnishIntegrationTestCase {

  public function casesMarvinTestUnit(): array {
    $root = $this->getMarvinRootDir();

    return [
      'basic good' => [
        [
          'exitCode' => 0,
          'stdError' => [
            "[PHPUnit - Run] runs",
            "cd '$root/tests/fixtures/project_01'",
            implode(' ', [
              "php '../../../vendor/bin/phpunit'",
              "--colors='never'",
              "--testsuite='Unit'",
              "'docroot/modules/custom/dummy_m1/tests/src/Unit/DummyM1GoodTest.php'\"",
            ]),
          ],
          'stdOutput' => '(1 test, 1 assertion)',
        ],
        [
          'docroot/modules/custom/dummy_m1/tests/src/Unit/DummyM1GoodTest.php',
        ],
      ],
      'basic bad' => [
        [
          'exitCode' => 1,
          'stdError' => [
            '[PHPUnit - Run] runs',
            "cd '$root/tests/fixtures/project_01'",
            implode(' ', [
              "php '../../../vendor/bin/phpunit'",
              "--colors='never'",
              "--testsuite='Unit'",
              "'docroot/modules/custom/dummy_m1/tests/src/Unit/DummyM1BadTest.php'\"" . PHP_EOL,
              '[Sweetchuck\Robo\PHPUnit\Task\RunTask]   ' . PHP_EOL,
              '[Sweetchuck\Robo\PHPUnit\Task\RunTask]  Exit code 1',
            ]),
          ],
          'stdOutput' => 'Tests: 1, Assertions: 1, Failures: 1.',
        ],
        [
          'docroot/modules/custom/dummy_m1/tests/src/Unit/DummyM1BadTest.php',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesMarvinTestUnit
   */
  public function testMarvinTestUnit(array $expected, array $args = []): void {
    $root = $this->getMarvinRootDir();

    if ($args) {
      array_unshift($args, '--');
    }

    array_unshift($args, "$root/tests/fixtures/project_01");

    $this->drush(
      'marvin:test:unit',
      $args,
      $this->getCommonCommandLineOptions(),
      NULL,
      NULL,
      $expected['exitCode'],
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    foreach ($expected['stdError'] ?? [] as $index => $expectedStcError) {
      static::assertStringContainsString($expectedStcError, $actualStdError, "stdError-$index");
    }
    static::assertStringEndsWith($expected['stdOutput'], $actualStdOutput, 'stdOutput');
  }

}

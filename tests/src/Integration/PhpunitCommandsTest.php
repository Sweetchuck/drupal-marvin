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
    $phpBinDir = PHP_BINDIR;

    return [
      'basic good' => [
        [
          'exitCode' => 0,
          'stdError' => implode(' ', [
            '[notice] runs',
            "\"cd '$root/tests/fixtures/project_01'",
            '&&',
            "$phpBinDir/phpdbg -qrr",
            "'../../../bin/phpunit'",
            "--colors='never'",
            "--testsuite='Unit'",
            "'docroot/modules/custom/dummy_m1/tests/src/Unit/DummyM1GoodTest.php'\"",
          ]),
          'stdOutput' => '(1 test, 1 assertion)',
        ],
        [
          'docroot/modules/custom/dummy_m1/tests/src/Unit/DummyM1GoodTest.php',
        ],
      ],
      'basic bad' => [
        [
          'exitCode' => 1,
          'stdError' => implode(' ', [
            '[notice] runs',
            "\"cd '$root/tests/fixtures/project_01'",
            '&&',
            "$phpBinDir/phpdbg -qrr",
            "'../../../bin/phpunit'",
            "--colors='never'",
            "--testsuite='Unit'",
            "'docroot/modules/custom/dummy_m1/tests/src/Unit/DummyM1BadTest.php'\"" . PHP_EOL,
            '[error]   ' . PHP_EOL,
            '[error]  Exit code 1',
          ]),
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
      $expected['exitCode']
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertSame($expected['stdError'], $actualStdError, 'stdError');
    static::assertStringEndsWith($expected['stdOutput'], $actualStdOutput, 'stdOutput');
  }

}

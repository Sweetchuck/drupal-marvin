<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\PhpcsCommandsBase<extended>
 * @covers \Drupal\marvin\CommandDelegatorTrait
 * @covers \Drupal\marvin\Robo\PhpcsConfigFallbackTaskLoader
 * @covers \Drupal\marvin\Robo\Task\PhpcsConfigFallbackTask<extended>
 */
class PhpcsCommandsTest extends UnishIntegrationTestCase {

  public function testMarvinLintPhpcsProject(): void {
    $root = $this->getMarvinRootDir();

    $expected = [
      'exitCode' => 2,
      'stdError' => implode(PHP_EOL, [
        '[notice] ',
        " [notice] notice runs cd '$root/tests/fixtures/project_01' && ../../../bin/phpcs --standard='Drupal,DrupalPractice' --report='json' -- 'drush/custom/' 'docroot/modules/custom/' 'docroot/themes/custom/'",
        ' [error]  PHP Code Sniffer found some errors :-( ',
        ' [error]  Exit code 2',
      ]),
      'stdOutput' => 'Missing class doc comment',
    ];

    $this->drush(
      'marvin:lint:phpcs',
      ["$root/tests/fixtures/project_01"],
      [],
      $expected['exitCode']
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertContains($expected['stdOutput'], $actualStdOutput, 'stdOutput');
    static::assertSame($expected['stdError'], $actualStdError, 'stdError');
  }

  public function testMarvinLintPhpcsModule(): void {
    $root = $this->getMarvinRootDir();

    $expected = [
      'exitCode' => 2,
      'stdError' => implode(PHP_EOL, [
        '[notice] ',
        " [notice] notice runs cd '$root/tests/fixtures/project_01/docroot/modules/custom/dummy_m1' && ../../../../../../../bin/phpcs --standard='Drupal,DrupalPractice' --report='json' -- 'Commands/' 'dummy_m1.module'",
        ' [error]  PHP Code Sniffer found some errors :-( ',
        ' [error]  Exit code 2',
      ]),
      'stdOutput' => 'Missing class doc comment',
    ];

    $this->drush(
      'marvin:lint:phpcs',
      ["$root/tests/fixtures/project_01/docroot/modules/custom/dummy_m1"],
      [],
      $expected['exitCode']
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertSame($expected['stdError'], $actualStdError, 'stdError');
    static::assertContains($expected['stdOutput'], $actualStdOutput, 'stdOutput');
  }

}

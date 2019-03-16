<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\ComposerCommandsBase<extended>
 */
class ComposerCommandsTest extends UnishIntegrationTestCase {

  public function testLintComposerValidate(): void {
    $root = $this->getMarvinRootDir();

    $expected = [
      'exitCode' => 0,
      'stdError' => implode(PHP_EOL, [
        '[notice] Validating composer.json: composer validate',
        " [notice] Running composer validate in $root/tests/fixtures/project_01",
        ' [success] Done in ',
      ]),
      'stdOutput' => './composer.json is valid',
    ];

    $this->drush(
      'marvin:lint:composer-validate',
      ["$root/tests/fixtures/project_01"],
      [],
      $expected['exitCode']
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertStringStartsWith($expected['stdError'], $actualStdError, 'stdError');
    static::assertSame($expected['stdOutput'], $actualStdOutput, 'stdOutput');
  }

}

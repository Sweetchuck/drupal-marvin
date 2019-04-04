<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\ComposerCommandsBase<extended>
 * @covers \Drupal\marvin\CommandDelegatorTrait
 */
class ComposerCommandsTest extends UnishIntegrationTestCase {

  public function testLintComposerValidate(): void {
    $root = $this->getMarvinRootDir();
    //$projectRoot = $this->getProjectRootDir();

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
      $this->getCommonCommandLineOptions(),
      NULL,
      NULL,
      $expected['exitCode'],
      NULL,
      [
        'HOME' => '/dev/null',
      ]
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertStringStartsWith($expected['stdError'], $actualStdError, 'stdError');
    static::assertSame($expected['stdOutput'], $actualStdOutput, 'stdOutput');
  }

}

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
        '[Marvin - PHP_CodeSniffer config fallback] ',
        " [PHP_CodeSniffer - lint files] runs cd '$root/tests/fixtures/project_01' && ../../../vendor/bin/phpcs --standard='Drupal,DrupalPractice' --report='json' -- 'docroot/modules/custom/' 'docroot/themes/custom/'",
        ' [Sweetchuck\Robo\Phpcs\Task\PhpcsLintFiles]  PHP Code Sniffer found some errors :-( ',
        ' [Sweetchuck\Robo\Phpcs\Task\PhpcsLintFiles]  Exit code 2',
      ]),
      'stdOutput' => 'Missing class doc comment',
    ];

    $this->drush(
      'marvin:lint:phpcs',
      ["$root/tests/fixtures/project_01"],
      $this->getCommonCommandLineOptions(),
      NULL,
      NULL,
      $expected['exitCode'],
      NULL,
      $this->getCommonCommandLineEnvVars()
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertStringContainsString($expected['stdOutput'], $actualStdOutput, 'stdOutput');
    static::assertSame($expected['stdError'], $actualStdError, 'stdError');
  }

  public function testMarvinLintPhpcsModule(): void {
    $root = $this->getMarvinRootDir();

    $expected = [
      'exitCode' => 2,
      'stdError' => implode(PHP_EOL, [
        '[Marvin - PHP_CodeSniffer config fallback] ',
        " [PHP_CodeSniffer - lint files] runs cd '$root/tests/fixtures/project_01/docroot/modules/custom/dummy_m1' && ../../../../../../../vendor/bin/phpcs --standard='Drupal,DrupalPractice' --report='json' -- 'Commands/' 'tests/' 'dummy_m1.module'",
        ' [Sweetchuck\Robo\Phpcs\Task\PhpcsLintFiles]  PHP Code Sniffer found some errors :-( ',
        ' [Sweetchuck\Robo\Phpcs\Task\PhpcsLintFiles]  Exit code 2',
      ]),
      'stdOutput' => 'Missing class doc comment',
    ];

    $this->drush(
      'marvin:lint:phpcs',
      ["$root/tests/fixtures/project_01/docroot/modules/custom/dummy_m1"],
      $this->getCommonCommandLineOptions(),
      NULL,
      NULL,
      $expected['exitCode'],
      NULL,
      $this->getCommonCommandLineEnvVars()
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertSame($expected['stdError'], $actualStdError, 'stdError');
    static::assertStringContainsString($expected['stdOutput'], $actualStdOutput, 'stdOutput');
  }

}

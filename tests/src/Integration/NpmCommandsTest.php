<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

use PHPUnit\Framework\SkippedTestError;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\NpmCommandsBase<extended>
 * @covers \Drupal\marvin\CommandDelegatorTrait
 */
class NpmCommandsTest extends UnishIntegrationTestCase {

  public function testNpmInstall(): void {
    throw new SkippedTestError('@todo install nvm');

    $root = $this->getMarvinRootDir();

    $nvmDir = getenv('REAL_NVM_DIR');

    $expected = [
      'exitCode' => 0,
      'stdError' => implode(PHP_EOL, [
        '[notice] themes/custom/dummy_t1/package.json',
        ' [notice] ',
        " [notice] runs \". '$nvmDir/nvm.sh'; nvm which '11.5.0'\"",
        " [notice] cd 'themes/custom/dummy_t1' && $nvmDir/versions/node/v11.5.0/bin/node $nvmDir/versions/node/v11.5.0/bin/yarn install",
      ]),
      'stdOutput' => '',
    ];

    $envVars = [
      'NVM_DIR' =>  $nvmDir,
    ];
    $options = $this->getCommonCommandLineOptions();

    $this->drush(
      'marvin:npm:install',
      ["$root/tests/fixtures/project_01/docroot/themes/custom/dummy_t1"],
      $options,
      NULL,
      NULL,
      $expected['exitCode'],
      NULL,
      $envVars
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertSame($expected['stdError'], $actualStdError, 'stdError');
    static::assertSame($expected['stdOutput'], $actualStdOutput, 'stdOutput');
  }

}

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
    $nvmDir = getenv('REAL_NVM_DIR');
    if (!$nvmDir) {
      throw new SkippedTestError('${REAL_NVM_DIR} is empty. NVM has to be installed.');
    }

    $root = $this->getMarvinRootDir();

    $expected = [
      'exitCode' => 0,
      'stdError' => implode('', [
        '@^',
        preg_quote('[Progress] themes/custom/dummy_t1/package.json', '@') . '\n',
        preg_quote(' [Marvin - Node detector] ', '@') . '\n',
        preg_quote(" [NVM - Which] runs \". '$nvmDir/nvm.sh'; nvm which '14.17'\"", '@') . '\n',
        preg_quote(" [Yarn - Install] cd './themes/custom/dummy_t1' && $nvmDir/versions/node/v__VERSION__/bin/node $nvmDir/versions/node/v__VERSION__/bin/yarn install", '@'),
        '$@',
      ]),
    ];

    $expected['stdError'] = strtr(
      $expected['stdError'],
      [
        '__VERSION__' => '14\.17\.\d+',
      ],
    );

    $envVars = [
      'NVM_DIR' => $nvmDir,
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
      $envVars,
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertMatchesRegularExpression(
      $expected['stdError'],
      $actualStdError,
      'stdError',
    );

    static::assertStringContainsString(
      'yarn install',
      $actualStdOutput,
      'stdOutput',
    );
    static::assertStringContainsString(
      'Done in ',
      $actualStdOutput,
      'stdOutput',
    );
  }

}

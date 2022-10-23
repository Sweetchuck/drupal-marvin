<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\DrushConfigCommands<extended>
 * @covers \Drupal\marvin\CommandDelegatorTrait
 */
class DrushConfigCommandsTest extends UnishIntegrationTestCase {

  public function testDrushConfig(): void {
    $options = $this->getCommonCommandLineOptions();
    $options['format'] = 'json';
    $this->drush(
      'marvin:drush-config',
      [],
      $options,
      NULL,
      NULL,
      0,
      NULL,
      $this->getCommonCommandLineEnvVars()
    );

    $actualDrushConfig = $this->getOutputFromJSON();
    $topLevelKeys = [
      'drush',
      'marvin',
      'env',
      'options',
      'runtime',
    ];

    static::assertSame($topLevelKeys, array_keys($actualDrushConfig));
  }

}

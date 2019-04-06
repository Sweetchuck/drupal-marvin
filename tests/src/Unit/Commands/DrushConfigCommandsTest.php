<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Consolidation\Config\Config;
use Drupal\Tests\marvin\Unit\TaskTestBase;
use Drush\Commands\marvin\DrushConfigCommands;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\DrushConfigCommands<extended>
 */
class DrushConfigCommandsTest extends TaskTestBase {

  public static function casesDrushConfig(): array {
    return [
      'empty' => [
        [
          'drush' => [
            'vendor-dir' => '.',
          ],
          'options' => [
            'decorated' => FALSE,
            'interactive' => TRUE,
          ],
        ],
        [],
        [],
      ],
    ];
  }

  /**
   * @dataProvider casesDrushConfig
   */
  public function testDrushConfig(array $expected, array $configData, array $options) {
    $this->config->addContext('foo', new Config($configData));

    $commands = new DrushConfigCommands();
    $commands->setConfig($this->config);

    $actual = $commands->drushConfig($options);
    static::assertSame($expected, $actual);
  }

}

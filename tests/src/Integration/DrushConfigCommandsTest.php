<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 */
class DrushConfigCommandsTest extends UnishIntegrationTestCase {

  public function testDrushConfig(): void {
    $this->drush(
      'marvin:drush-config',
      [],
      [
        'format' => 'json',
      ],
      0
    );

    $actualDrushConfig = (array) $this->getOutputFromJSON();
    $topLevelKeys = [
      'drush',
      'marvin',
      'options',
      'env',
      'runtime',
      'backend',
    ];

    static::assertSame($topLevelKeys, array_keys($actualDrushConfig));
  }

}

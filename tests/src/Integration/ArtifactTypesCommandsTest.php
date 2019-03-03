<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

class ArtifactTypesCommandsTest extends UnishIntegrationTestCase {

  public function testArtifactTypes() {
    $this->drush(
      'marvin:artifact:types',
      [],
      [
        'format' => 'json',
      ],
      0
    );

    $expected = [
      'dummy' => [
        'label' => "Dummy - 'integrationTest'",
        'description' => 'Do not use it',
        'id' => 'dummy',
        'weight' => 0,
      ],
    ];

    static::assertSame(
      json_encode($expected, JSON_PRETTY_PRINT) . PHP_EOL,
      $this->getOutputRaw()
    );
  }

}

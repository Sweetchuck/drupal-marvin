<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 */
class ArtifactTypesCommandsTest extends UnishIntegrationTestCase {

  public function testArtifactTypesJson() {
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
        'label' => "Dummy - integrationTest",
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

  public function testArtifactTypesTable() {
    $this->drush(
      'marvin:artifact:types',
      [],
      [
        'format' => 'table',
      ],
      0
    );

    $expected = implode(PHP_EOL, [
      ' ID    Label                   Description   ',
      " dummy Dummy - integrationTest Do not use it ",
      '',
    ]);

    static::assertSame($expected, $this->getOutputRaw());
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Functional\Drush\Commands;

use Drush\TestTraits\DrushTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MarvinArtifactTypeListCommandTest extends TestCase {

  use DrushTestTrait;

  public static function casesExecuteSuccess(): array {
    return [
      'default' => [
        'expected' => [
          'stdOutput' => implode(
            "\n",
            [
              ' ---- ------- ------------- ',
              '  ID   Label   Description  ',
              ' ---- ------- ------------- ',
              '',
            ],
          ),
          'exitCode' => 0,
        ],
        'arguments' => [],
        'options' => [
          'no-ansi' => TRUE,
        ],
      ],
      'yaml' => [
        'expected' => [
          'stdOutput' => <<< 'YAML'
            {  }

            YAML,
          'exitCode' => 0,
        ],
        'arguments' => [],
        'options' => [
          'no-ansi' => TRUE,
          'format' => 'yaml',
        ],
      ],
    ];
  }

  #[Test]
  #[DataProvider('casesExecuteSuccess')]
  public function testExecuteSuccess(
    array $expected,
    array $arguments,
    array $options,
  ): void {
    $this->drush(
      'marvin:artifact-type',
      $arguments,
      $options,
      NULL,
      NULL,
      $expected['exitCode'] ?? 0,
    );

    if (array_key_exists('stdOutput', $expected)) {
      $actualStdOutput = $this->getOutputRaw();
      static::assertSame($expected['stdOutput'], $actualStdOutput);
    }

    if (array_key_exists('stdError', $expected)) {
      $actualStdError = $this->getErrorOutputRaw();
      static::assertStringContainsString($expected['stdError'], $actualStdError);
    }
  }

}

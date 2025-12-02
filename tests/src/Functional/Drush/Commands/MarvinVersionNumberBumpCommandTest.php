<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Functional\Drush\Commands;

use Drush\TestTraits\DrushTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MarvinVersionNumberBumpCommandTest extends TestCase {

  use DrushTestTrait;

  public static function casesExecuteSuccess(): array {
    return [
      'major-s0-m0' => [
        'expected' => '2.0.0',
        'arguments' => [
          '1.2.3',
          'major',
        ],
        'options' => [],
      ],
      'major-s1-m0' => [
        'expected' => '2.0.0',
        'arguments' => [
          '1.2.3-beta1',
          'major',
        ],
        'options' => [],
      ],
      'major-s1-m1' => [
        'expected' => '2.0.0',
        'arguments' => [
          '1.2.3-beta1+foo',
          'major',
        ],
        'options' => [],
      ],
      'major-s0-m1' => [
        'expected' => '2.0.0',
        'arguments' => [
          '1.2.3+foo',
          'major',
        ],
        'options' => [],
      ],
      'major-s0-m1-p1-m1' => [
        'expected' => '2.0.0-rc1+bar',
        'arguments' => [
          '1.2.3+foo',
          'major',
        ],
        'options' => [
          'preRelease' => 'rc1',
          'metadata' => 'bar',
        ],
      ],
      'minor-s0-m0' => [
        'expected' => '1.3.0',
        'arguments' => [
          '1.2.3',
          'minor',
        ],
        'options' => [],
      ],
      'minor-s1-m0' => [
        'expected' => '1.3.0',
        'arguments' => [
          '1.2.3-beta1',
          'minor',
        ],
        'options' => [],
      ],
      'minor-s1-m1' => [
        'expected' => '1.3.0',
        'arguments' => [
          '1.2.3-beta1+foo',
          'minor',
        ],
        'options' => [],
      ],
      'minor-s0-m1' => [
        'expected' => '1.3.0',
        'arguments' => [
          '1.2.3+foo',
          'minor',
        ],
        'options' => [],
      ],
      'minor-s0-m1-p1-m1' => [
        'expected' => '1.3.0-rc1+bar',
        'arguments' => [
          '1.2.3-beta1+foo',
          'minor',
        ],
        'options' => [
          'preRelease' => 'rc1',
          'metadata' => 'bar',
        ],
      ],
      'patch-s0-m0' => [
        'expected' => '1.2.4',
        'arguments' => [
          '1.2.3',
          'patch',
        ],
        'options' => [],
      ],
      'patch-s1-m0' => [
        'expected' => '1.2.4',
        'arguments' => [
          '1.2.3-beta1',
          'patch',
        ],
        'options' => [],
      ],
      'patch-s1-m1' => [
        'expected' => '1.2.4',
        'arguments' => [
          '1.2.3-beta1+foo',
          'patch',
        ],
        'options' => [],
      ],
      'patch-s0-m1' => [
        'expected' => '1.2.4',
        'arguments' => [
          '1.2.3+foo',
          'patch',
        ],
        'options' => [],
      ],
      'patch-s0-m1-p1-m1' => [
        'expected' => '1.2.4-rc1+bar',
        'arguments' => [
          '1.2.3-beta1+foo',
          'patch',
        ],
        'options' => [
          'preRelease' => 'rc1',
          'metadata' => 'bar',
        ],
      ],
      'preRelease-s0-m0' => [
        'expected' => '1.2.4-alpha1',
        'arguments' => [
          '1.2.3',
          'preRelease',
        ],
        'options' => [],
      ],
      'preRelease-s1-m0' => [
        'expected' => '1.2.3-beta2',
        'arguments' => [
          '1.2.3-beta1',
          'preRelease',
        ],
        'options' => [],
      ],
      'preRelease-s1-m1' => [
        'expected' => '1.2.3-beta2',
        'arguments' => [
          '1.2.3-beta1+foo',
          'preRelease',
        ],
        'options' => [],
      ],
      'preRelease-s0-m1' => [
        'expected' => '1.2.4-alpha1',
        'arguments' => [
          '1.2.3+foo',
          'preRelease',
        ],
        'options' => [],
      ],
      'preRelease-s1-m1-p1-m1' => [
        'expected' => '1.2.3-beta2+bar',
        'arguments' => [
          '1.2.3-beta1+foo',
          'preRelease',
        ],
        'options' => [
          'metadata' => 'bar',
        ],
      ],
    ];
  }

  #[Test]
  #[DataProvider('casesExecuteSuccess')]
  public function testExecuteSuccess(
    string $expected,
    array $arguments,
    array $options,
  ): void {
    $this->drush(
      'marvin:version-number:bump',
      $arguments,
      $options,
    );
    $this->assertOutputEquals($expected);
  }

}

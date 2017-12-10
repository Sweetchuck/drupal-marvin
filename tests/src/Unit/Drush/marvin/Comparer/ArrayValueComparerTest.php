<?php

declare(strict_types = 1);

namespace Drush\marvin\Tests\Unit\Comparer;

use Drush\marvin\Comparer\ArrayValueComparer;
use PHPUnit\Framework\TestCase;

class ArrayValueComparerTest extends TestCase {

  public function casesCompare(): array {
    return [
      'empty' => [
        [],
        [],
        [],
      ],
      'without keys' => [
        [
          'i2' => ['k2' => 2],
          'i1' => ['k1' => 1],
        ],
        [
          'i2' => ['k2' => 2],
          'i1' => ['k1' => 1],
        ],
        [],
      ],
      'basic' => [
        [
          'i1' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 1],
          'i2' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 2],
          'i3' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 3],
          'i4' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 4],
        ],
        [
          'i4' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 4],
          'i2' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 2],
          'i1' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 1],
          'i3' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 3],
        ],
        ['k1' => 0, 'k2' => 0, 'k3' => 0, 'k4' => 0],
      ],
      'basic descending' => [
        [
          'i4' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 4],
          'i3' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 3],
          'i2' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 2],
          'i1' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 1],
        ],
        [
          'i4' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 4],
          'i2' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 2],
          'i1' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 1],
          'i3' => ['k1' => 1, 'k2' => 1, 'k3' => 1, 'k4' => 3],
        ],
        ['k1' => 0, 'k2' => 0, 'k3' => 0, 'k4' => 0],
        FALSE,
      ],
    ];
  }

  /**
   * @dataProvider casesCompare
   */
  public function testCompare(array $expected, array $items, array $keys, ?bool $ascending = NULL): void {
    $comparer = new ArrayValueComparer($keys);
    if ($ascending !== NULL) {
      $comparer->setAscending($ascending);
    }

    uasort($items, $comparer);
    $this->assertSame($expected, $items);
  }

}

<?php

declare(strict_types = 1);

namespace Drush\marvin\Tests\Unit\Filter;

use Drush\marvin\Composer\ArrayFilterEnabled;
use PHPUnit\Framework\TestCase;

class ArrayFilterEnabledTest extends TestCase {

  public function casesCheck(): array {
    return [
      'empty' => [
        [],
        [],
      ],
      'basic' => [
        [
          'a' => [
            'enabled' => TRUE,
          ],
          'c' => [],
        ],
        [
          'a' => [
            'enabled' => TRUE,
          ],
          'b' => [
            'enabled' => FALSE,
          ],
          'c' => [],
        ],
      ],
      'basic; inverse' => [
        [
          'b' => [
            'enabled' => FALSE,
          ],
        ],
        [
          'a' => [
            'enabled' => TRUE,
          ],
          'b' => [
            'enabled' => FALSE,
          ],
          'c' => [],
        ],
        NULL,
        TRUE,
      ],
      'defaultValue: false' => [
        [
          'a' => [
            'enabled' => TRUE,
          ],
        ],
        [
          'a' => [
            'enabled' => TRUE,
          ],
          'b' => [
            'enabled' => FALSE,
          ],
          'c' => [],
        ],
        NULL,
        NULL,
        FALSE,
      ],
      'custom key' => [
        [
          'a' => [
            'custom' => TRUE,
          ],
          'c' => [],
        ],
        [
          'a' => [
            'custom' => TRUE,
          ],
          'b' => [
            'custom' => FALSE,
          ],
          'c' => [],
        ],
        'custom',
      ],
    ];
  }

  /**
   * @dataProvider casesCheck
   */
  public function testCheck(array $expected, array $items, ?string $key = NULL, ?bool $inverse = NULL, ?bool $defaultValue = NULL): void {
    $filter = new ArrayFilterEnabled($key, $inverse, $defaultValue);
    $this->assertSame($expected, array_filter($items, $filter));
  }

}

<?php

namespace Drush\Commands\Marvin\Tests\Unit\ArrayUtils;

use Drush\marvin\ArrayUtils\FilterArrayUtils;
use PHPUnit\Framework\TestCase;

class FilterArrayUtilsTest extends TestCase {

  public function casesFilterEnabled(): array {
    return [
      'empty' => [
        [],
        [],
      ],
      'basic' => [
        [
          'a' => TRUE,
          'c' => ['enabled' => TRUE],
          'e' => [],
          'f' => (object) ['enabled' => TRUE],
          'h' => (object) [],
        ],
        [
          'a' => TRUE,
          'b' => FALSE,
          'c' => ['enabled' => TRUE],
          'd' => ['enabled' => FALSE],
          'e' => [],
          'f' => (object) ['enabled' => TRUE],
          'g' => (object) ['enabled' => FALSE],
          'h' => (object) [],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesFilterEnabled
   */
  public function testFilterEnabled(
    array $expected,
    array $items,
    string $property = 'enabled',
    bool $default = TRUE
  ): void {
    $this->assertEquals($expected, FilterArrayUtils::filterEnabled($items, $property, $default));
  }

}

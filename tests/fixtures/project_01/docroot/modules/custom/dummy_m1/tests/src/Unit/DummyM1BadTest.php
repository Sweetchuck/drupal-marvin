<?php

declare(strict_types = 1);

namespace Drupal\Tests\dummy_m1\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @group dummy_m1
 * @group MarvinBad
 */
class DummyM1BadTest extends UnitTestCase {

  public function testDummyGood() {
    static::assertTrue(FALSE);
  }

}

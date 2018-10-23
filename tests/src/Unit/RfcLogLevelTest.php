<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit;

use Drupal\marvin\RfcLogLevel;
use PHPUnit\Framework\TestCase;

class RfcLogLevelTest extends TestCase {

  public function testGetLevels(): void {
    static::assertCount(8, RfcLogLevel::getLevels());
  }

}

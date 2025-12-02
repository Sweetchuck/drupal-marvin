<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit;

use Drupal\marvin\RfcLogLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('marvin')]
#[CoversClass(RfcLogLevel::class)]
class RfcLogLevelTest extends TestCase {

  public function testGetLevels(): void {
    static::assertCount(8, RfcLogLevel::getLevels());
  }

}

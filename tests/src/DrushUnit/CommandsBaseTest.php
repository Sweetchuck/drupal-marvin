<?php

namespace Drush\Commands\marvin\Tests\Unit;

use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Framework\TestCase;

class CommandsBaseTest extends TestCase {

  public function testDummy() {
    throw new IncompleteTestError();
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Functional\Drush\Commands;

use Drush\TestTraits\DrushTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class MarvinDrushConfigCommandTest extends TestCase {

  use DrushTestTrait;

  #[Test]
  public function testExecuteSuccess(): void {
    $arguments = [];
    $options = [];

    $this->drush(
      'marvin:drush-config',
      $arguments,
      $options,
    );
    $output = $this->getOutput();
    $data = Yaml::parse($output);
    static::assertArrayHasKey('marvin', $data);
    static::assertArrayHasKey('php', $data['marvin']);
  }

}

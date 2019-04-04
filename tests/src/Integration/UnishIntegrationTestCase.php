<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;
use Webmozart\PathUtil\Path;

class UnishIntegrationTestCase extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function convertKeyValueToFlag($key, $value) {
    if ($value === NULL) {
      return "--$key";
    }

    $options = [];

    if (!is_iterable($value)) {
      $value = [$value];
    }

    foreach ($value as $item) {
      $options[] = sprintf('--%s=%s', $key, static::escapeshellarg($item));
    }

    return implode(' ', $options);
  }

  protected function getCommonCommandLineOptions() {
    return [
      'config' => [
        Path::join($this->getDrupalRoot(), '..', 'drush'),
      ],
    ];
  }

  protected function getCommonCommandLineEnvVars() {
    return [
      'HOME' => '/dev/null',
    ];
  }

  protected function getProjectRootDir(): string {
    return dirname($this->getDrupalRoot());
  }

  public function getMarvinRootDir(): string {
    return dirname(__DIR__, 3);
  }

}

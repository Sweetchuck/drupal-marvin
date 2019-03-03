<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

use Unish\UnishIntegrationTestCase as UnishIntegrationTestCaseBase;
use Webmozart\PathUtil\Path;

class UnishIntegrationTestCase extends UnishIntegrationTestCaseBase {

  /**
   * {@inheritdoc}
   */
  protected function getCommonCommandLineOptions() {
    return [
      'config' => [
        Path::join(static::getSut(), 'drush'),
      ],
    ] + parent::getCommonCommandLineOptions();
  }

}

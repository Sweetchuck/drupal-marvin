<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drupal\marvin\PhpVariantTrait;
use Drupal\marvin\Utils as MarvinUtils;
use Drush\Commands\marvin\PhpunitCommandsBase;
use Robo\Collection\CollectionBuilder;
use Webmozart\PathUtil\Path;

class PhpunitCommands extends PhpunitCommandsBase {

  use PhpVariantTrait;

  /**
   * @command marvin:test:unit
   */
  public function marvinTestUnit(
    string $workingDirectory,
    array $args,
    array $options = []
  ): CollectionBuilder {
    $marvinRootDir = MarvinUtils::marvinRootDir();
    $phpunitExecutable = Path::makeRelative("$marvinRootDir/vendor/bin/phpunit", $workingDirectory);

    $testSuiteNames = ['Unit'];
    $phpVariant = $this->createPhpVariantFromCurrent();

    return $this
      ->getTaskPhpUnit($this->geDefaultPhpunitTaskOptions($phpVariant))
      ->setWorkingDirectory($workingDirectory)
      ->setHideStdOutput(FALSE)
      ->setColors('never')
      ->setPhpunitExecutable($phpunitExecutable)
      ->setTestSuite($testSuiteNames)
      ->setArguments($args);
  }

}

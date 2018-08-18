<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin\Unish\Artifact;

use Drush\Commands\Tests\marvin\Unish\CommandsTestBase;

class MarvinComposerTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUpDrupalNeedsToBeInstalled(): bool {
    return TRUE;
  }

  public function testMarvinComposer() {
    $this->prepareEnableModules();

    $this->caseMarvinComposerPostInstallCmd();
    $this->caseMarvinComposerPostUpdateCmd();
  }

  protected function prepareEnableModules() {
    $this->drush(
      'pm:enable',
      ['dummy_m1'],
      ['yes' => NULL],
      NULL,
      static::getSut()
    );
  }

  protected function caseMarvinComposerPostInstallCmd() {
    $expectedExitCode = 0;
    $expectedStdOutput = '';
    $expectedStdError = '';

    $this->drush(
      'marvin:composer:post-install-cmd',
      [],
      $this->getDefaultDrushCommandOptions(),
      NULL,
      static::getSut(),
      $expectedExitCode
    );

    $this->assertSame($expectedStdOutput, $this->getOutput());
    $this->assertSame($expectedStdError, $this->getErrorOutput());
  }

  protected function caseMarvinComposerPostUpdateCmd() {
    $expectedExitCode = 0;
    $expectedStdOutput = '';
    $expectedStdError = '';

    $this->drush(
      'marvin:composer:post-update-cmd',
      [],
      $this->getDefaultDrushCommandOptions(),
      NULL,
      static::getSut(),
      $expectedExitCode
    );

    $this->assertSame($expectedStdOutput, $this->getOutput());
    $this->assertSame($expectedStdError, $this->getErrorOutput());
  }

}

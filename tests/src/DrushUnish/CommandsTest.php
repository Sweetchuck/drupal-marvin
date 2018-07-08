<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin\Unish\Artifact;

use Drush\Commands\Tests\marvin\Unish\CommandsTestBase;
use Symfony\Component\Yaml\Yaml;

class CommandsTest extends CommandsTestBase {

  public function testAllInOne() {
    $this->prepareEnableModules();

    $this->caseMarvinArtifactTypes();
    $this->caseMarvinArtifactTypesFormatTable();
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

  protected function caseMarvinArtifactTypes() {
    $this->logHeader(__FUNCTION__);

    $expectedExitCode = 0;
    $expectedStdOutput = [
      'dummy' => [
        'label' => "Dummy - 'unish'",
        'description' => 'Do not use it',
        'id' => 'dummy',
        'weight' => 0,
      ],
    ];
    $expectedStdError = '';

    $this->drush(
      'marvin:artifact:types',
      [],
      $this->getDefaultDrushCommandOptions(),
      NULL,
      static::getSut(),
      $expectedExitCode
    );

    $actualStdOutput = Yaml::parse($this->getOutput());
    $actualStdError = $this->getErrorOutput();

    $this->assertSame($expectedStdOutput, $actualStdOutput);
    $this->assertSame($expectedStdError, $actualStdError);
  }

  protected function caseMarvinArtifactTypesFormatTable() {
    $this->logHeader(__FUNCTION__);

    $expectedExitCode = 0;
    $expectedStdOutput = implode(PHP_EOL, [
      'ID    Label           Description   ',
      " dummy Dummy - 'unish' Do not use it",
    ]);
    $expectedStdError = '';

    $args = [];
    $options = $this->getDefaultDrushCommandOptions();
    $options['format'] = 'table';

    $this->drush(
      'marvin:artifact:types',
      $args,
      $options,
      NULL,
      static::getSut(),
      $expectedExitCode
    );

    $actualStdOutput = $this->getOutput();
    $actualStdError = $this->getErrorOutput();

    $this->assertSame($expectedStdOutput, $actualStdOutput);
    $this->assertSame($expectedStdError, $actualStdError);
  }

  protected function caseMarvinComposerPostInstallCmd() {
    $this->logHeader(__FUNCTION__);
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
    $this->logHeader(__FUNCTION__);

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

  protected function logHeader(string $message) {
    $this->log("--== $message ==--");
  }

}

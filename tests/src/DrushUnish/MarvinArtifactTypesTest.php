<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin\Unish;

use Symfony\Component\Yaml\Yaml;

class MarvinArtifactTypesTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUpDrupalNeedsToBeInstalled(): bool {
    return TRUE;
  }

  public function testMarvinArtifactTypes() {
    $this->caseBasic();
    $this->caseFormatTable();
  }

  protected function caseBasic() {
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

  protected function caseFormatTable() {
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

}

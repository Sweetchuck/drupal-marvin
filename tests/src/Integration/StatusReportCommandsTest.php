<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

use Drupal\marvin\RfcLogLevel;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\StatusReportCommands<extended>
 * @covers \Drupal\marvin\CommandDelegatorTrait
 */
class StatusReportCommandsTest extends UnishIntegrationTestCase {

  public static function casesMarvinStatusReport(): array {
    return [
      'info' => [
        [
          'stdOutput' => implode(PHP_EOL, [
            'e1_id:',
            '  id: e1_id',
            '  title: e1_ti',
            '  value: e1_va',
            '  description: e1_de',
            '  severity: 6',
            '  severityName: Info',
          ]),
        ],
        RfcLogLevel::INFO,
      ],
      'error' => [
        [
          'exitCode' => RfcLogLevel::ERROR + 1,
          'stdOutput' => implode(PHP_EOL, [
            'e1_id:',
            '  id: e1_id',
            '  title: e1_ti',
            '  value: e1_va',
            '  description: e1_de',
            '  severity: 3',
            '  severityName: Error',
          ]),
        ],
        RfcLogLevel::ERROR,
      ],
      'error table' => [
        [
          'exitCode' => RfcLogLevel::ERROR + 1,
          'stdOutput' => implode(PHP_EOL, [
            'Severity Title Value Description ',
            ' Error    e1_ti e1_va e1_de',
          ]),
        ],
        RfcLogLevel::ERROR,
        [
          'no-ansi' => NULL,
          'format' => 'table',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesMarvinStatusReport
   */
  public function testMarvinStatusReport(array $expected, int $severity, array $options = []): void {
    $expected += [
      'stdError' => '',
      'stdOutput' => '',
      'exitCode' => 0,
    ];

    $envVars = [
      'MARVIN_SEVERITY' => $severity,
    ];

    $this->drush(
      'marvin:status-report',
      [],
      $options + $this->getCommonCommandLineOptions(),
      NULL,
      NULL,
      $expected['exitCode'],
      NULL,
      $envVars + $this->getCommonCommandLineEnvVars()
    );

    $actualStdError = $this->getErrorOutput();
    $actualStdOutput = $this->getOutput();

    static::assertSame($expected['stdError'], $actualStdError, 'StdError');
    static::assertSame($expected['stdOutput'], $actualStdOutput, 'StdOutput');
  }

}

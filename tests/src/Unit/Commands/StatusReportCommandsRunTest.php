<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\StatusReport\StatusReportEntry;
use Drupal\marvin\StatusReport\StatusReportInterface;
use Drupal\Tests\marvin\Unit\TaskTestBase;
use Drush\Commands\marvin\StatusReportCommands;

class StatusReportCommandsRunTest extends TaskTestBase {

  public static function casesStatusReport(): array {
    $n1 = StatusReportEntry::__set_state([
      'id' => 'n1',
      'severity' => RfcLogLevel::NOTICE,
    ]);

    $n2 = StatusReportEntry::__set_state([
      'id' => 'n2',
      'severity' => RfcLogLevel::NOTICE,
    ]);

    $w1 = StatusReportEntry::__set_state([
      'id' => 'w1',
      'severity' => RfcLogLevel::WARNING,
    ]);

    $e1 = StatusReportEntry::__set_state([
      'id' => 'e1',
      'severity' => RfcLogLevel::ERROR,
    ]);

    return [
      'empty' => [[], []],
      'basic' => [
        [
          $e1->getId() => $e1,
          $n1->getId() => $n1,
          $n2->getId() => $n2,
          $w1->getId() => $w1,
        ],
        [[$n1], [$w1, $n2], [$e1]],
      ],
    ];
  }

  /**
   * @dataProvider casesStatusReport
   */
  public function testStatusReport(array $expected, array $entriesCollection, array $options = []): void {
    $hookManager = new HookManager();

    foreach ($entriesCollection as $entries) {
      $hookManager->add(
        $this->createHookCallbackForMarvinStatusReport($entries),
        'on-event',
        'marvin:status-report'
      );
    }

    $commands = new StatusReportCommands();
    $commands->setConfig($this->config);
    $commands->setHookManager($hookManager);

    $commandResult = $commands->statusReport($options);

    /** @var \Drupal\marvin\StatusReport\StatusReportInterface $actualStatusReport */
    $actualStatusReport = $commandResult->getOutputData();

    static::assertInstanceOf(StatusReportInterface::class, $actualStatusReport);
    static::assertSameSize($expected, $actualStatusReport, 'Number of status report entries');

    /** @var string $entryId */
    /** @var \Drupal\marvin\StatusReport\StatusReportEntryInterface $actual */
    foreach ($actualStatusReport as $entryId => $actual) {
      static::assertSame(
        $expected[$entryId]->jsonSerialize(),
        $actual->jsonSerialize(),
        sprintf('%s = %s', $expected[$entryId]->getId(), $actual->getId())
      );
    }
  }

  protected function createHookCallbackForMarvinStatusReport(array $entries): callable {
    return function () use ($entries): array {
      return $entries;
    };
  }

}

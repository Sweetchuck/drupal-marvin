<?php

declare(strict_types = 1);

namespace Drush\Commands\dummy_m1;

use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\StatusReport\StatusReportEntry;
use Drush\Commands\marvin\CommandsBase;

class StatusReportSubscriberCommands extends CommandsBase {

  /**
   * @hook on-event marvin:status-report
   *
   * @return \Drupal\marvin\StatusReport\StatusReportEntryInterface[]
   */
  public function onEventMarvinStatusReport(): array {
    $severity = getenv('MARVIN_SEVERITY');
    if ($severity === FALSE) {
      $severity = RfcLogLevel::INFO;
    }

    return [
      StatusReportEntry::__set_state([
        'id' => 'e1_id',
        'title' => 'e1_ti',
        'value' => 'e1_va',
        'description' => 'e1_de',
        'severity' => (int) $severity,
      ]),
    ];
  }

}

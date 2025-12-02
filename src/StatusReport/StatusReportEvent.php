<?php

declare(strict_types=1);

namespace Drupal\marvin\StatusReport;

use Symfony\Contracts\EventDispatcher\Event;

/**
 *
 */
class StatusReportEvent extends Event {

  public const string EVENT_COLLECT = 'marvin.status_report.collect';

  public const string EVENT_ALTER = 'marvin.status_report.alter';

  /**
   * @phpstan-param \Drupal\marvin\StatusReport\StatusReportInterface<string, \Drupal\marvin\StatusReport\StatusReportEntryInterface> $statusReport
   */
  public function __construct(
    public readonly StatusReportInterface $statusReport,
  ) {
  }

}

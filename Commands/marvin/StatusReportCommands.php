<?php

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\marvin\StatusReport\StatusReport;
use Drupal\marvin\StatusReport\StatusReportInterface;
use Drupal\marvin\Utils as MarvinUtils;

class StatusReportCommands extends CommandsBase {

  /**
   * {@inheritdoc}
   */
  protected static $classKeyPrefix = 'marvin.statusReport';

  /**
   * {@inheritdoc}
   */
  protected $customEventNamePrefix = 'marvin:status-report';

  /**
   * @command marvin:status-report
   * @bootstrap none
   * @default-string-field id
   * @default-fields severityName,title,value,description
   * @field-labels
   *   id: ID
   *   title: Title
   *   value: Value
   *   description: Description
   *   severity: Severity ID
   *   severityName: Severity
   */
  public function statusReport(
    array $options = [
      'format' => 'yaml',
      'fields' => '',
      'include-field-labels' => TRUE,
      'table-style' => 'compact',
    ]
  ): StatusReportInterface {
    return (new StatusReport())
      ->addEntries(...array_values($this->collectStatusReportEntries()));
  }

  /**
   * @hook alter marvin:status-report
   */
  public function hookAlterMarvinStatusReport($statusReport, CommandData $commandData) {
    if ($statusReport instanceof StatusReportInterface) {
      $expectedFormat = $commandData->input()->getOption('format');
      if ($expectedFormat === 'table') {
        return MarvinUtils::convertStatusReportToRowsOfFields($statusReport);
      }

      return $statusReport->jsonSerialize();
    }

    return $statusReport;
  }

  /**
   * @return \Drupal\marvin\StatusReport\StatusReportEntryInterface[]
   */
  protected function collectStatusReportEntries(): array {
    $customEventHandlers = $this->getCustomEventHandlers($this->getCustomEventName(''));
    $entries = [];
    foreach ($customEventHandlers as $customEventHandler) {
      /** @var \Drupal\marvin\StatusReport\StatusReportEntryInterface[] $result */
      $result = $customEventHandler($this->input(), $this->output());
      foreach ($result as $entry) {
        $entries[$entry->getId()] = $entry;
      }
    }

    ksort($entries);

    return $entries;
  }

}

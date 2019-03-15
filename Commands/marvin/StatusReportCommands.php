<?php

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
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
  ): CommandResult {
    $statusReport = (new StatusReport())
      ->addEntries(...array_values($this->collectStatusReportEntries()));

    return CommandResult::dataWithExitCode($statusReport, $statusReport->getExitCode());
  }

  /**
   * @hook alter marvin:status-report
   */
  public function hookAlterMarvinStatusReport(CommandResult $result, CommandData $commandData) {
    $statusReport = $result->getOutputData();
    if ($statusReport instanceof StatusReportInterface) {
      $expectedFormat = $commandData->input()->getOption('format');
      switch ($expectedFormat) {
        case 'table':
          $statusReport = MarvinUtils::convertStatusReportToRowsOfFields($statusReport);
          break;

        default:
          $statusReport = $statusReport->jsonSerialize();
          break;
      }

      $result->setOutputData($statusReport);
    }
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

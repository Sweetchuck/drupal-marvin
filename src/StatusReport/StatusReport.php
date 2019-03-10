<?php

declare(strict_types = 1);

namespace Drupal\marvin\StatusReport;

use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\Utils;

class StatusReport implements StatusReportInterface {

  /**
   * @var \Drupal\marvin\StatusReport\StatusReportEntryInterface[]
   */
  protected $entries = [];

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->entries);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->entries);
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    $data = [];
    foreach ($this->entries as $entry) {
      $data[$entry->getId()] = $entry->jsonSerialize();
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputData() {
    return $this->jsonSerialize();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Fail on warning.
   */
  public function getExitCode() {
    return Utils::getExitCodeBasedOnSeverity($this->getHighestSeverity());
  }

  /**
   * {@inheritdoc}
   */
  public function addEntries(StatusReportEntryInterface ...$entries) {
    foreach ($entries as $entry) {
      $this->entries[$entry->getId()] = $entry;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeEntries(...$entries) {
    foreach ($entries as $entry) {
      $entryId = $entry instanceof StatusReportEntryInterface ? $entry->getId() : $entry;
      unset($this->entries[$entryId]);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAllEntries() {
    $this->entries = [];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestSeverity(): ?int {
    $highestSeverity = NULL;
    foreach ($this->entries as $entry) {
      $severity = $entry->getSeverity();
      if ($highestSeverity === NULL || $severity < $highestSeverity) {
        $highestSeverity = $severity;
      }

      if ($highestSeverity === RfcLogLevel::EMERGENCY) {
        break;
      }
    }

    return $highestSeverity;
  }

}

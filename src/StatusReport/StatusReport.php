<?php

declare(strict_types = 1);

namespace Drupal\marvin\StatusReport;

use Drupal\marvin\RfcLogLevel;

class StatusReport implements StatusReportInterface {

  /**
   * @var \Drupal\marvin\StatusReport\StatusReportEntryInterface[]
   */
  protected $entries = [];

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

  public function getOutputData() {
    return $this->jsonSerialize();
  }

  /**
   * {@inheritdoc}
   */
  public function getExitCode() {
    // @todo
    return 0;

    $severity = $this->getHighestSeverity();

    return $severity === NULL || $severity > RfcLogLevel::ERROR ? 0 : $severity + 1;
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
      if ($entry instanceof StatusReportEntryInterface) {
        unset($this->entries[$entry->getId()]);

        continue;
      }

      if (is_scalar($entry)) {
        unset($this->entries[$entry]);

        continue;
      }

      throw new \InvalidArgumentException('@todo', 1);
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

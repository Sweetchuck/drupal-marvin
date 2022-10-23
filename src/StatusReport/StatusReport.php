<?php

declare(strict_types = 1);

namespace Drupal\marvin\StatusReport;

use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\Utils;

class StatusReport implements StatusReportInterface {

  /**
   * @var \Drupal\marvin\StatusReport\StatusReportEntryInterface[]
   */
  protected array $entries = [];

  protected int $lowestSeverityAssError = RfcLogLevel::ERROR;

  /**
   * {@inheritdoc}
   */
  public function count(): int {
    return count($this->entries);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Iterator {
    return new \ArrayIterator($this->entries);
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    $data = [];
    foreach ($this->entries as $entry) {
      $data[$entry->getId()] = $entry->jsonSerialize();
    }

    return $data;
  }

  public function getOutputData() {
    return $this->jsonSerialize();
  }

  public function getExitCode() {
    return Utils::getExitCodeBasedOnSeverity(
      $this->getHighestSeverity(),
      $this->getLowestSeverityAsError()
    );
  }

  public function addEntries(iterable $entries): static {
    foreach ($entries as $entry) {
      $this->entries[$entry->getId()] = $entry;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeEntries(...$entries): static {
    foreach ($entries as $entry) {
      $entryId = $entry instanceof StatusReportEntryInterface ? $entry->getId() : $entry;
      unset($this->entries[$entryId]);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAllEntries(): static {
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

  public function getLowestSeverityAsError(): int {
    return $this->lowestSeverityAssError;
  }

  public function setLowestSeverityAsError(int $severity): static {
    $this->lowestSeverityAssError = $severity;

    return $this;
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\marvin\StatusReport;

use Consolidation\AnnotatedCommand\ExitCodeInterface;
use Consolidation\AnnotatedCommand\OutputDataInterface;

interface StatusReportInterface extends
    \JsonSerializable,
    \IteratorAggregate,
    \Countable,
    OutputDataInterface,
    ExitCodeInterface {

  /**
   * @phpstan-param iterable<\Drupal\marvin\StatusReport\StatusReportEntryInterface> $entries
   */
  public function addEntries(iterable $entries): static;

  /**
   * @param string|\Drupal\marvin\StatusReport\StatusReportEntryInterface ...$entries
   */
  public function removeEntries(...$entries): static;

  public function removeAllEntries(): static;

  /**
   * @return int|null
   *   Returns NULL or one of the constants from \Drupal\marvin\RfcLogLevel.
   */
  public function getHighestSeverity(): ?int;

}

<?php

declare(strict_types = 1);

namespace Drupal\marvin\StatusReport;

use Consolidation\AnnotatedCommand\ExitCodeInterface;
use Consolidation\AnnotatedCommand\OutputDataInterface;
use Countable;
use IteratorAggregate;
use JsonSerializable;

interface StatusReportInterface extends
    JsonSerializable,
    IteratorAggregate,
    Countable,
    OutputDataInterface,
    ExitCodeInterface {

  /**
   * @return $this
   */
  public function addEntries(StatusReportEntryInterface ...$entries);

  /**
   * @param string|\Drupal\marvin\StatusReport\StatusReportEntryInterface ...
   *
   * @return $this
   */
  public function removeEntries(...$entries);

  /**
   * @return $this
   */
  public function removeAllEntries();

  /**
   * @return int|null
   *   Returns NULL or one of the constants from \Drupal\marvin\RfcLogLevel.
   */
  public function getHighestSeverity(): ?int;

}

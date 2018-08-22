<?php

declare(strict_types = 1);

namespace Drupal\marvin\StatusReport;

interface StatusReportEntryInterface extends \JsonSerializable {

  public function getId(): string;

  /**
   * @return $this
   */
  public function setId(string $id);

  public function getTitle(): string;

  /**
   * @return $this
   */
  public function setTitle(string $title);

  public function getValue(): string;

  /**
   * @return $this
   */
  public function setValue(string $value);

  public function getDescription(): string;

  /**
   * @return $this
   */
  public function setDescription(string $description);

  public function getSeverity(): int;

  /**
   * @see \Drupal\Core\Logger\RfcLogLevel::getLevels
   *
   * @return $this
   */
  public function setSeverity(int $severity);

}

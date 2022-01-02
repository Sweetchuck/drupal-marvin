<?php

declare(strict_types = 1);

namespace Drupal\marvin\StatusReport;

use Drupal\marvin\RfcLogLevel;

class StatusReportEntry implements StatusReportEntryInterface {

  /**
   * @see http://php.net/manual/en/language.oop5.magic.php#object.set-state
   *
   * @return static
   */
  public static function __set_state(array $properties) {
    $entry = new static();
    if (array_key_exists('id', $properties)) {
      $entry->setId($properties['id']);
    }

    if (array_key_exists('title', $properties)) {
      $entry->setTitle($properties['title']);
    }

    if (array_key_exists('value', $properties)) {
      $entry->setValue($properties['value']);
    }

    if (array_key_exists('description', $properties)) {
      $entry->setDescription($properties['description']);
    }

    if (array_key_exists('severity', $properties)) {
      $entry->setSeverity($properties['severity']);
    }

    return $entry;
  }

  /**
   * @var string
   */
  protected $id = '';

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId(string $id) {
    $this->id = $id;

    return $this;
  }

  /**
   * @var string
   */
  protected $title = '';

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title) {
    $this->title = $title;

    return $this;
  }

  /**
   * @var string
   */
  protected $value = '';

  /**
   * {@inheritdoc}
   */
  public function getValue(): string {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue(string $value) {
    $this->value = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $description = '';

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $description) {
    $this->description = $description;

    return $this;
  }

  /**
   * @var int
   */
  protected $severity = 0;

  /**
   * {@inheritdoc}
   */
  public function getSeverity(): int {
    return $this->severity;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeverity(int $severity) {
    $this->severity = $severity;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    $severityNames = RfcLogLevel::getLevels();
    $severity = $this->getSeverity();

    return [
      'id' => $this->getId(),
      'title' => $this->getTitle(),
      'value' => $this->getValue(),
      'description' => $this->getDescription(),
      'severity' => $severity,
      'severityName' => $severityNames[$severity],
    ];
  }

}

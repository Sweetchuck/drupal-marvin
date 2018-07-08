<?php

declare(strict_types = 1);

namespace Drupal\marvin\Filter;

class ArrayFilterEnabled extends ArrayFilterBase {

  /**
   * @var string
   */
  protected $key = 'enabled';

  public function getKey(): string {
    return $this->key;
  }

  /**
   * @return $this
   */
  public function setKey(string $value) {
    $this->key = $value;

    return $this;
  }

  /**
   * @var bool
   */
  protected $defaultValue = TRUE;

  public function getDefaultValue(): bool {
    return $this->defaultValue;
  }

  /**
   * @return $this
   */
  public function setDefaultValue(bool $value) {
    $this->defaultValue = $value;

    return $this;
  }

  public function __construct(?string $key = NULL, ?bool $inverse = NULL, ?bool $defaultValue = NULL) {
    if ($key !== NULL) {
      $this->setKey($key);
    }

    if ($inverse !== NULL) {
      $this->setInverse($inverse);
    }

    if ($defaultValue !== NULL) {
      $this->setDefaultValue($defaultValue);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function check($array, ?string $outerKey = NULL): bool {
    $key = $this->getKey();
    $value = array_key_exists($key, $array) ?
      $array[$key]
      : $this->getDefaultValue();

    return $this->getInverse() ?
      !$value
      : (bool) $value;
  }

}

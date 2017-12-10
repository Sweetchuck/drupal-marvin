<?php

namespace Drush\marvin\Comparer;

class ArrayValueComparer extends BaseComparer {

  /**
   * @var array
   */
  protected $keys = [];

  public function getKeys(): array {
    return $this->keys;
  }

  /**
   * @return $this
   */
  public function setKeys(array $value) {
    $this->keys = $value;

    return $this;
  }

  public function __construct(array $keys = []) {
    $this->setKeys($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function setResult($a, $b) {
    foreach ($this->getKeys() as $key => $defaultValue) {
      $aValue = array_key_exists($key, $a) ? $a[$key] : $defaultValue;
      $bValue = array_key_exists($key, $b) ? $b[$key] : $defaultValue;

      $this->result = $aValue <=> $bValue;

      if ($this->result !== 0) {
        break;
      }
    }

    return $this;
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\marvin\Filter;

abstract class ArrayFilterBase implements ArrayFilterInterface {

  /**
   * @var bool
   */
  protected $inverse = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getInverse(): bool {
    return $this->inverse;
  }

  /**
   * {@inheritdoc}
   */
  public function setInverse(bool $value) {
    $this->inverse = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke($value, ?string $key = NULL): bool {
    return $this->check($value, $key);
  }

}

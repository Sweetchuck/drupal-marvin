<?php

declare(strict_types = 1);

namespace Drupal\marvin\Comparer;

interface ComparerInterface {

  public function getAscending(): bool;

  /**
   * @return $this
   */
  public function setAscending(bool $ascending);

  public function __invoke($a, $b): int;

  public function compare($a, $b): int;

}

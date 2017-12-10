<?php

declare(strict_types = 1);

namespace Drush\marvin\Composer;

interface ArrayFilterInterface {

  public function getInverse(): bool;

  /**
   * @return $this
   */
  public function setInverse(bool $value);

  /**
   * @param array|\ArrayAccess $value
   * @param null|string $key
   *
   * @return bool
   */
  public function __invoke($value, ?string $key = NULL): bool;

  /**
   * @param array|\ArrayAccess $value
   * @param null|string $key
   *
   * @return bool
   */
  public function check($value, ?string $key = NULL): bool;

}

<?php

namespace Drupal\marvin\ArrayUtils;

class FilterArrayUtils {

  /**
   * @deprecated
   * @see \Drupal\marvin\Composer\ArrayFilterEnabled
   */
  public static function filterEnabled(array $items, string $property = 'enabled', bool $default = TRUE): array {
    $filtered = [];

    foreach ($items as $key => $value) {
      if ((is_scalar($value) || is_bool($value)) && $value) {
        $filtered[$key] = $value;
      }
      elseif (is_object($value)
        && (
          (property_exists($value, $property) && $value->$property)
          || (!property_exists($value, $property) && $default)
        )
      ) {
        $filtered[$key] = $value;
      }
      elseif (is_array($value)
        && (
          (array_key_exists($property, $value) && $value[$property])
          || (!array_key_exists($property, $value) && $default)
        )
      ) {
        $filtered[$key] = $value;
      }
    }

    return $filtered;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\marvin\ArtifactType;

use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\StructuredData\ConversionInterface;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * @extends \ArrayObject<string, marvin-artifact-type>
 */
class ArtifactTypeList extends \ArrayObject implements \JsonSerializable, ConversionInterface {

  /**
   * {@inheritdoc}
   *
   * @return array<string, array<string, mixed>>
   */
  public function jsonSerialize(): array {
    return $this->getArrayCopy();
  }

  /**
   * {@inheritdoc}
   */
  public function convert(FormatterOptions $options): mixed {
    $hasToBeRows = in_array($options->getFormat(), ['table', 'csv', 'tsv']);
    if ($hasToBeRows) {
      return $this->convertToRowsOfFields();
    }

    return $this->jsonSerialize();
  }

  public function convertToRowsOfFields(): RowsOfFields {
    return new RowsOfFields($this->jsonSerialize());
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, array<string, mixed>>
   */
  public function __serialize(): array {
    return $this->getArrayCopy();
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, array<string, mixed>> $data
   */
  public function __unserialize(array $data): void {
    $this->exchangeArray($data);
  }

  public function populateDefaultValues(): static {
    foreach ($this as $id => &$info) {
      $info['id'] = $id;
      $info += ['weight' => 0];
    }

    return $this;
  }

}

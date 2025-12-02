<?php

declare(strict_types=1);

namespace Drupal\marvin\VersionNumber;

use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\StructuredData\ConversionInterface;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Sweetchuck\Utils\VersionNumber;

class StructuredData implements ConversionInterface {

  public function __construct(
    public readonly VersionNumber $versionNumber,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function convert(FormatterOptions $options): mixed {
    if ($options->getFormat() === 'string') {
      return (string) $this->versionNumber;
    }

    $hasToBeRows = in_array($options->getFormat(), ['table', 'csv', 'tsv']);
    if ($hasToBeRows) {
      return $this->convertToRowsOfFields($options);
    }

    return $this->versionNumber->jsonSerialize();
  }

  public function convertToRowsOfFields(FormatterOptions $options): RowsOfFields {
    return new RowsOfFields([$this->versionNumber->jsonSerialize()]);
  }

}

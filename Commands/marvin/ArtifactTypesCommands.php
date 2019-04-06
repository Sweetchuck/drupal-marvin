<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Sweetchuck\Utils\Comparer\ArrayValueComparer;

class ArtifactTypesCommands extends ArtifactCommandsBase {

  /**
   * @var array
   */
  protected $types = [];

  /**
   * Lists all available artifact types.
   *
   * @command marvin:artifact:types
   * @bootstrap none
   * @default-string-field id
   * @default-fields id,label,description
   * @field-labels
   *   id: ID
   *   label: Label
   *   description: Description
   *   weight: Weight
   */
  public function artifactTypes(
    array $options = [
      'format' => 'yaml',
      'fields' => '',
      'include-field-labels' => TRUE,
      'table-style' => 'compact',
    ]
  ): array {
    return $this
      ->collectArtifactTypes()
      ->expandArtifactTypes()
      ->sortArtifactTypes()
      ->types;
  }

  /**
   * @hook alter marvin:artifact:types
   */
  public function hookAlterMarvinArtifactTypes($result, CommandData $commandData) {
    $expectedFormat = $commandData->input()->getOption('format');
    if ($expectedFormat === 'table' && is_array($result)) {
      return $this->convertArtifactTypesToRowsOfFields($result);
    }

    return $result;
  }

  /**
   * @return $this
   */
  protected function collectArtifactTypes() {
    $projectType = $this->getConfig()->get('marvin.projectType');

    /** @var callable[] $eventHandlers */
    $eventHandlers = $this->getCustomEventHandlers($this->getCustomEventName('types'));
    $this->types = [];
    foreach ($eventHandlers as $eventHandler) {
      $this->types += $eventHandler($projectType);
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function expandArtifactTypes() {
    foreach ($this->types as $id => &$info) {
      $info['id'] = $id;
      $info += ['weight' => 0];
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function sortArtifactTypes() {
    uasort($this->types, $this->getArtifactTypesComparer());

    return $this;
  }

  protected function getArtifactTypesComparer(): callable {
    return new ArrayValueComparer($this->getArtifactTypesComparerConfig());
  }

  protected function getArtifactTypesComparerConfig(): array {
    return [
      'weight' => 0,
      'label' => '',
      'id' => '',
    ];
  }

  protected function convertArtifactTypesToRowsOfFields(array $artifactTypes): RowsOfFields {
    return new RowsOfFields($artifactTypes);
  }

}

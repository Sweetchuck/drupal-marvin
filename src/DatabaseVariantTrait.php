<?php

declare(strict_types = 1);

namespace Drupal\marvin;

trait DatabaseVariantTrait {

  /**
   * @return \Consolidation\Config\ConfigInterface
   *
   * @see \Robo\Common\ConfigAwareTrait::getConfig
   */
  abstract public function getConfig();

  protected function getConfigDatabaseVariants(): array {
    $databaseVariants = [];

    $items = (array) $this->getConfig()->get('marvin.database.variant', []);

    foreach ($items as $id => $item) {
      $databaseVariants[$id] = $this->loadDatabaseVariant($id, $item);
    }

    return $databaseVariants;
  }

  protected function loadDatabaseVariant(string $id, array $item): array {
    $item['id'] = $id;
    $item += [
      'enabled' => TRUE,
      'type' => 'mysql',
      'binDir' => '/usr/bin',
    ];

    return $item;
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\marvin;

trait DatabaseVariantTrait {

  protected function getConfigDatabaseVariants(): array {
    $phpVariants = [];

    $items = (array) $this->getConfig()->get('command.marvin.settings.database.variant', []);

    foreach ($items as $id => $item) {
      $phpVariants[$id] = $this->loadDatabaseVariant($id, $item);
    }

    return $phpVariants;
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

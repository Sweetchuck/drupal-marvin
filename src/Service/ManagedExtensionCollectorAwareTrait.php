<?php

namespace Drush\marvin\Service;

trait ManagedExtensionCollectorAwareTrait {

  /**
   * @var ManagedExtensionCollectorInterface
   */
  protected $managedExtensionCollector;

  public function getManagedExtensionCollector(): ?ManagedExtensionCollectorInterface {
    return $this->managedExtensionCollector;
  }

  public function setManagedExtensionCollector(ManagedExtensionCollectorInterface $collector) {
    $this->managedExtensionCollector = $collector;

    return $this;
  }

  public function hasManagedExtensionCollector(): bool {
    return isset($this->managedExtensionCollector);
  }

}

<?php

namespace Drush\marvin\Service;

interface ManagedExtensionCollectorAwareInterface {

  public function getManagedExtensionCollector(): ?ManagedExtensionCollectorInterface;

  /**
   * @return $this
   */
  public function setManagedExtensionCollector(ManagedExtensionCollectorInterface $collector);

  public function hasManagedExtensionCollector(): bool;

}

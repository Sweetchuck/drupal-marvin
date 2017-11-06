<?php

namespace Drush\Commands\Marvin;

use Drush\marvin\Service\ManagedExtensionCollector;
use Drush\marvin\Service\ManagedExtensionCollectorAwareInterface;
use Drush\marvin\Service\ManagedExtensionCollectorAwareTrait;
use League\Container\ContainerInterface;

class QaCommandsBase extends CommandsBase implements ManagedExtensionCollectorAwareInterface {

  use ManagedExtensionCollectorAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    $container->share('marvin.managed_extension_collector', ManagedExtensionCollector::class);

    return parent::setContainer($container);
  }

}

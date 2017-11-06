<?php

namespace Drush\Commands\Marvin;

use Drush\marvin\Service\ManagedExtensionCollector;
use League\Container\ContainerInterface;

class QaCommandsBase extends CommandsBase {

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    if (!$container->has('marvin.managed_extension_collector')) {
      $container->share(
        'marvin.managed_extension_collector',
        ManagedExtensionCollector::class
      );
    }

    return parent::setContainer($container);
  }

}

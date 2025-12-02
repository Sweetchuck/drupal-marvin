<?php

declare(strict_types=1);

namespace Drupal\marvin;

use League\Container\Argument\ResolvableArgument;
use League\Container\Container as LeagueContainer;
use Psr\Container\ContainerInterface;

abstract class ContainerInitializerBase {

  public static function initialize(ContainerInterface $container): void {
    if (static::isInitialized($container)) {
      // Early exit. Assumes that every other service is also initialized.
      return;
    }

    static::addServiceInfoList(static::getServiceInfoList(), $container);
  }

  /**
   * Child classes should override this method.
   *
   * @code
   * return $container->has(MyService::class);
   * @endcode
   */
  public static function isInitialized(ContainerInterface $container): bool {
    return FALSE;
  }

  /**
   * @phpstan-return array<string, array<string, mixed>>
   */
  public static function getServiceInfoList(): array {
    return [];
  }

  /**
   * @phpstan-param array<string, array<string, mixed>> $serviceInfoList
   */
  public static function addServiceInfoList(array $serviceInfoList, ContainerInterface $container): void {
    foreach ($serviceInfoList as $serviceId => $serviceInfo) {
      $serviceInfo += [
        'shared' => TRUE,
      ];

      static::addServiceInfo($serviceId, $serviceInfo, $container);
    }
  }

  /**
   * @param string $id
   * @param array<string, mixed> $serviceInfo
   * @param \Psr\Container\ContainerInterface $container
   */
  public static function addServiceInfo(string $id, array $serviceInfo, ContainerInterface $container): void {
    if ($container->has($id)) {
      return;
    }

    $serviceInfo += [
      'shared' => TRUE,
    ];

    if ($container instanceof LeagueContainer) {
      $definition = $container->add($id, $serviceInfo['class']);
      $definition->setShared($serviceInfo['shared']);

      foreach ($serviceInfo['arguments'] ?? [] as $argInfo) {
        if (is_string($argInfo)
          && str_starts_with($argInfo, '@')
        ) {
          $definition->addArgument(new ResolvableArgument(substr($argInfo, 1)));
        }
      }
    }
  }

}

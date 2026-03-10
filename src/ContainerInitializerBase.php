<?php

declare(strict_types=1);

namespace Drupal\marvin;

use League\Container\Argument\ResolvableArgument;
use League\Container\Container as LeagueContainer;
use Psr\Container\ContainerInterface;

abstract class ContainerInitializerBase {

  /**
   * @phpstan-return array<class-string<\Drupal\marvin\ContainerInitializerBase>>
   */
  public static function getDependencies(): array {
    return [];
  }

  public static function initialize(ContainerInterface $container): void {
    foreach (static::getDependencies() as $dependency) {
      $dependency::initialize($container);
    }

    static::addServiceInfoList(static::getServiceInfoList(), $container);
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

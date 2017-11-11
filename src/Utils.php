<?php

namespace Drush\marvin;

use Stringy\Stringy;

class Utils {

  /**
   * Drupal related composer package types.
   *
   * @var string[]
   */
  public static $drupalPackageTypes = [
    'drupal-core' => TRUE,
    'drupal-drush' => TRUE,
    'drupal-module' => TRUE,
    'drupal-profile' => TRUE,
    'drupal-theme' => TRUE,
  ];

  public static function commandClassNameToConfigIdentifier(string $className): string {
    return (string) (new Stringy($className))
      ->regexReplace('^\\\\?Drush\\\\Commands\\\\', '')
      ->regexReplace('Commands$', '')
      ->replace('\\', '.')
      ->underscored()
      ->regexReplace('(?<=\.)((qa\.lint)_)(?=[^\.]+$)', '\\2.');
  }

  /**
   * Check that a composer package is Drupal related or not.
   *
   * @param array $package
   *   Composer package definition.
   *
   * @return bool
   *   Return TRUE is the $package is Drupal related.
   */
  public static function isDrupalPackage(array $package): bool {
    return !empty(static::$drupalPackageTypes[$package['type']]);
  }

  public static function collectManagedDrupalExtensions(
    string $rootProjectDir,
    array $composerLock,
    array $packagePaths
  ): array {
    $managedExtensions = [];
    foreach ($packagePaths as $packageName => $packagePath) {
      foreach (['packages', 'packages-dev'] as $lockKey) {
        // @todo Do we need a package without ".git" dir?
        if (isset($composerLock[$lockKey][$packageName])
          && static::isDrupalPackage($composerLock[$lockKey][$packageName])
          && mb_strpos($packagePath, $rootProjectDir) !== 0
        ) {
          $managedExtensions[$packageName] = $packagePath;
        }
      }
    }

    return $managedExtensions;
  }

}

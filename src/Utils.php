<?php

namespace Drush\marvin;

use Stringy\StaticStringy;
use Stringy\Stringy;
use Webmozart\PathUtil\Path;

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

  public static function marvinRootDir(): string {
    return Path::getDirectory(__DIR__);
  }

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
        if (file_exists("$packagePath/.git")
          && isset($composerLock[$lockKey][$packageName])
          && static::isDrupalPackage($composerLock[$lockKey][$packageName])
          && !StaticStringy::startsWith($packagePath, $rootProjectDir)
        ) {
          $managedExtensions[$packageName] = $packagePath;
        }
      }
    }

    return $managedExtensions;
  }

  public static function getComposerJsonFileName(): string {
    return getenv('COMPOSER') ?: 'composer.json';
  }

  public static function findFileUpward(string $fileName, string $absoluteDirectory = ''): string {
    if (!$absoluteDirectory) {
      $absoluteDirectory = getcwd();
    }

    while ($absoluteDirectory) {
      if (file_exists("$absoluteDirectory/$fileName")) {
        return $absoluteDirectory;
      }

      $parent = Path::getDirectory($absoluteDirectory);
      if ($parent === $absoluteDirectory) {
        break;
      }

      $absoluteDirectory = $parent;
    }

    return '';
  }

}

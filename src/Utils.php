<?php

declare(strict_types = 1);

namespace Drupal\marvin;

use Consolidation\AnnotatedCommand\CommandError;
use Stringy\Stringy;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * @todo Make a service out of this class.
 */
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

  public static $drupalPhpExtensions = [
    'engine' => TRUE,
    'install' => TRUE,
    'module' => TRUE,
    'php' => TRUE,
    'profile' => TRUE,
    'theme' => TRUE,
  ];

  /**
   * @todo https://packagist.org/packages/mindplay/composer-locator
   */
  public static function marvinRootDir(): string {
    return Path::getDirectory(__DIR__);
  }

  public static function commandClassNameToConfigIdentifier(string $className): string {
    return (string) (new Stringy($className))
      ->regexReplace('^\\\\?Drush\\\\Commands\\\\', '')
      ->regexReplace('Commands$', '')
      ->regexReplace('^marvin_([^\\\\]+)', 'marvin')
      ->replace('\\', '.')
      ->underscored()
      ->regexReplace('(?<=\.)((lint\.lint)_)(?=[^\.]+$)', 'lint.');
  }

  /**
   * Checks that a composer package is Drupal related or not.
   *
   * @param array $package
   *   Composer package definition.
   *
   * @return bool
   *   Return TRUE if the $package is Drupal related.
   */
  public static function isDrupalPackage(array $package): bool {
    return !empty(static::$drupalPackageTypes[$package['type']]);
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

  public static function getDirectDescendantDrupalPhpFiles(string $dir): array {
    $extensions = [];
    foreach (array_keys(static::$drupalPhpExtensions, TRUE) as $extension) {
      $extensions[] = preg_quote($extension);
    }

    if (!$extensions) {
      return [];
    }

    $namePattern = '/\.(' . implode('|', $extensions) . ')$/u';
    $files = (new Finder())
      ->depth('== 0')
      ->in($dir)
      ->name($namePattern);

    $fileNames = [];
    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($files as $file) {
      $fileNames[] = $file->getBasename();
    }

    return $fileNames;
  }

  public static function getDrupalExtensionVersionNumberPattern(): string {
    return implode('', [
      '/^',
      '(?P<coreMajor>\d+)',
      '\.',
      'x',
      '-',
      '(?P<extensionMajor>\d+)',
      '\.',
      '(?P<extensionMinor>\d+)',
      '(-(?P<extensionPreType>alpha|beta|rc)(?P<extensionPreMajor>\d+)){0,1}',
      '(\+(?P<extensionBuild>.+)){0,1}',
      '$/u',
    ]);
  }

  public static function isValidDrupalExtensionVersionNumber(string $versionNumber): bool {
    return (bool) preg_match(static::getDrupalExtensionVersionNumberPattern(), $versionNumber);
  }

  public static function parseDrupalExtensionVersionNumber(string $versionNumber): array {
    $pattern = static::getDrupalExtensionVersionNumberPattern();
    $matches = [];
    preg_match($pattern, $versionNumber, $matches);
    if (!$matches) {
      throw new \InvalidArgumentException('@todo');
    }

    $default = [
      'coreMajor' => 0,
      'extensionMajor' => 0,
      'extensionMinor' => 0,
      'extensionPreType' => '',
      'extensionPreMajor' => 0,
      'extensionBuild' => '',
    ];

    $matches += $default;

    settype($matches['coreMajor'], 'int');
    settype($matches['extensionMajor'], 'int');
    settype($matches['extensionMinor'], 'int');
    settype($matches['extensionPreMajor'], 'int');

    return array_intersect_key($matches, $default);
  }

  public static function escapeYamlValueString(string $text): string {
    return rtrim(mb_substr(Yaml::dump(['a' => $text]), 3));
  }

  public static function ensureTrailingEol(string &$text): void {
    if (!preg_match('/[\r\n]$/u', $text)) {
      $text .= PHP_EOL;
    }
  }

  /**
   * @param \Consolidation\AnnotatedCommand\CommandError[] $commandErrors
   */
  public static function aggregateCommandErrors(array $commandErrors): ?CommandError {
    $errorCode = 0;
    $messages = [];
    foreach (array_filter($commandErrors) as $commandError) {
      $messages[] = $commandError->getOutputData();
      $errorCode = max($errorCode, $commandError->getExitCode());
    }

    if ($messages) {
      return new CommandError(implode(PHP_EOL, $messages), $errorCode);
    }

    return NULL;
  }

}

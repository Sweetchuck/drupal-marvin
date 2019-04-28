<?php

declare(strict_types = 1);

namespace Drupal\marvin;

use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\marvin\StatusReport\StatusReportInterface;
use Icecave\SemVer\Version;
use Stringy\StaticStringy;
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
   * @var bool[]
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

  public static function drupalPhpExtensionPatterns(): array {
    return static::prefixSuffixItems(array_keys(static::$drupalPhpExtensions, TRUE), '*.');
  }

  public static function prefixSuffixItems(iterable $items, string $prefix = '', string $suffix = ''): array {
    $result = [];

    foreach ($items as $key => $value) {
      $result[$key] = "{$prefix}{$value}{$suffix}";
    }

    return $result;
  }

  public static function commandClassNameToConfigIdentifier(string $className): string {
    return (string) (new Stringy($className))
      ->regexReplace('^\\\\?Drush\\\\Commands\\\\', '')
      ->regexReplace('(Commands|CommandsBase)$', '')
      ->regexReplace('^marvin([^\\\\]*)', '')
      ->replace('\\', '.')
      ->dasherize()
      ->regexReplace('(?<=\.)(([^\.]+)\.(\2[_-]))(?=[^\.]+$)', '\2.');
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
      throw new \InvalidArgumentException('@todo', 1);
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

  public static function changeVersionNumberInYaml(string $yamlString, string $versionNumber): string {
    // Yaml::parse() and Yaml::dump() strips the comments.
    $escapedVersionNumber = Utils::escapeYamlValueString($versionNumber);

    $value = Yaml::parse($yamlString);
    if (array_key_exists('version', $value)) {
      // @todo This does not work with "version: |" and "version: >".
      return preg_replace(
        '/(?<=version: ).+/um',
        $escapedVersionNumber,
        $yamlString
      );
    }

    static::ensureTrailingEol($yamlString);

    return $yamlString . "version: $escapedVersionNumber" . PHP_EOL;
  }

  /**
   * @todo Deprecated. Use Stringy.
   */
  public static function ensureTrailingEol(string &$text): void {
    if (!preg_match('/[\r\n]$/u', $text)) {
      $text .= PHP_EOL;
    }
  }

  /**
   * @todo Probably this method is not necessary any more.
   */
  public static function phpUnitSuiteNameToNamespace(string $suitName): string {
    return StaticStringy::upperCamelize($suitName);
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

  public static function convertStatusReportToRowsOfFields(StatusReportInterface $statusReport): RowsOfFields {
    $data = $statusReport->jsonSerialize();
    $severityNames = RfcLogLevel::getLevels();
    foreach (array_keys($data) as $id) {
      $severity = $data[$id]['severity'];
      $severityName = $severityNames[$severity];
      $data[$id]['title'] = static::formatTextBySeverity($severity, $data[$id]['title']);
      $data[$id]['severity'] = static::formatTextBySeverity($severity, (string) $severity);
      $data[$id]['severityName'] = static::formatTextBySeverity($severity, $severityName);
    }

    return new RowsOfFields($data);
  }

  public static function formatTextBySeverity(int $severity, string $text): string {
    switch ($severity) {
      case RfcLogLevel::EMERGENCY:
      case RfcLogLevel::ALERT:
      case RfcLogLevel::CRITICAL:
      case RfcLogLevel::ERROR:
        return "<fg=red>$text</>";

      case RfcLogLevel::WARNING:
        return "<fg=yellow>$text</>";
    }

    return $text;
  }

  /**
   * @return string[]
   */
  public static function getGitHookNames(): array {
    return [
      'applypatch-msg',
      'commit-msg',
      'post-applypatch',
      'post-checkout',
      'post-commit',
      'post-merge',
      'post-receive',
      'post-rewrite',
      'post-update',
      'pre-applypatch',
      'pre-auto-gc',
      'pre-commit',
      'pre-push',
      'pre-rebase',
      'pre-receive',
      'prepare-commit-msg',
      'push-to-checkout',
      'update',
    ];
  }

  public static function splitPackageName(string $packageName): array {
    $parts = explode('/', $packageName, 2);
    if (count($parts) === 1) {
      array_unshift($parts, 'drupal');
    }

    return [
      'vendor' => $parts[0],
      'name' => $parts[1],
    ];
  }

  /**
   * @todo Do something on empty input.
   */
  public static function phpVersionToPhpVersionId(string $phpVersion): string {
    if (mb_strpos($phpVersion, '.') === FALSE) {
      // The input is already a version ID.
      return $phpVersion;
    }

    $phpVersionParts = explode('.', $phpVersion) + [1 => 0, 2 => 0];

    return sprintf(
      '%02d%02d%02d',
      $phpVersionParts[0],
      $phpVersionParts[1],
      $phpVersionParts[2]
    );
  }

  public static function phpErrorAll(string $phpVersion): int {
    $phpVersionMajorMinor = mb_substr(static::phpVersionToPhpVersionId($phpVersion), 0, 4);
    switch ($phpVersionMajorMinor) {
      case '0701':
      case '0702':
      case '0703':
        return 32767;
    }

    return E_ALL;
  }

  public static function dbUrl(array $connection): string {
    if ($connection['driver'] === 'sqlite') {
      return 'sqlite://' . $connection['database'];
    }

    $url = $connection['driver'] . '://';

    if (!empty($connection['username'])) {
      $url .= urlencode($connection['username']);

      if (!empty($connection['password'])) {
        $url .= ':' . urlencode($connection['password']);
      }

      $url .= '@';
    }

    $url .= $connection['host'];
    if (!empty($connection['port'])) {
      $url .= ':' . $connection['port'];
    }

    if (!empty($connection['database'])) {
      $url .= '/' . $connection['database'];
    }

    if (!empty($connection['prefix'])) {
      if (!empty($connection['prefix']['default'])) {
        $url .= '#' . $connection['prefix']['default'];
      }
      elseif (is_string($connection['prefix'])) {
        $url .= '#' . $connection['prefix'];
      }
    }

    return $url;
  }

  /**
   * @todo This could be a method on the ComposerInfo class.
   */
  public static function detectDrupalRootDir(ComposerInfo $composerInfo): string {
    $installerPaths = $composerInfo['extra']['installer-paths'] ?? [];
    foreach ($installerPaths as $installDir => $rules) {
      if (in_array('drupal/core', $rules) || in_array('type:drupal-core', $rules)) {
        return dirname($installDir);
      }
    }

    return $composerInfo['config']['vendor-dir'] . '/drupal';
  }

  public static function semverToDrupal(string $core, string $semver): string {
    $version = Version::parse($semver);
    $version->setPatch(99999);

    return str_replace('.99999', '', "{$core}-{$version}");
  }

  public static function drupalToSemver(string $drupalVersion): string {
    $parts = static::parseDrupalExtensionVersionNumber($drupalVersion);

    $semver = "{$parts['extensionMajor']}.{$parts['extensionMinor']}.0";

    if ($parts['extensionPreType']) {
      $semver .= "-{$parts['extensionPreType']}{$parts['extensionPreMajor']}";
    }

    if ($parts['extensionBuild']) {
      $semver .= "+{$parts['extensionBuild']}";
    }

    return $semver;
  }

  public static function incrementSemVersion(string $semver, string $fragment): Version {
    $version = Version::parse($semver);

    switch ($fragment) {
      case 'major':
        $version->setMinor(0);
      case 'minor':
        $version->setPatch(0);
      case 'patch':
        $version->setPreReleaseVersion(NULL);
      case 'pre-release':
      case 'preReleaseVersion':
        $version->setBuildMetaData(NULL);
        break;

      default:
        throw new \UnexpectedValueException('@todo Not implemented yet', 1);
    }

    switch ($fragment) {
      case 'major':
        $version->setMajor($version->major() + 1);
        break;

      case 'minor':
        $version->setMinor($version->minor() + 1);
        break;

      case 'patch':
        // @todo Not recommended to increment the "patch" part.
        $version->setPatch($version->patch() + 1);
        break;

      case 'pre-release':
      case 'preReleaseVersion':
        $preRelease = $version->preReleaseVersion();
        if (!$preRelease) {
          $version->setPatch($version->patch() + 1);
          $version->setPreReleaseVersion('alpha1');

          break;
        }

        $parts = static::parseSemVersionPreRelease($preRelease);
        if ($parts) {
          $version->setPreReleaseVersion(sprintf('%s%d', $parts['type'], $parts['number'] + 1));
        }

        break;
    }

    return $version;
  }

  public static function parseSemVersionPreRelease(string $preRelease): ?array {
    $pattern = '/^(?P<type>(alpha|beta|rc)\.?)(?P<number>\d+)$/ui';
    $matches = [];

    return preg_match($pattern, $preRelease, $matches) ?
      [
        'type' => $matches['type'],
        'number' => (int) $matches['number'],
      ]
      : NULL;
  }

  public static function pickFirstFile(array $dirs, array $files): ?array {
    foreach ($dirs as $dir) {
      foreach ($files as $file) {
        if (file_exists("$dir/$file")) {
          return [
            'dir' => $dir,
            'file' => $file,
          ];
        }
      }
    }

    return NULL;
  }

  public static function getTriStateCliOption(?bool $state, string $optionName): string {
    if ($state === NULL) {
      return '';
    }

    return $state ? "--$optionName" : "--no-$optionName";
  }

  public static function getExitCodeBasedOnSeverity(?int $severity, int $lowestError = RfcLogLevel::ERROR): int {
    return $severity === NULL || $severity > $lowestError ? 0 : $severity + 1;
  }

  public static function explodeCommaSeparatedList(string $items): array {
    return array_filter(
      preg_split('/\s*,\s*/', trim($items)),
      'mb_strlen'
    );
  }

}

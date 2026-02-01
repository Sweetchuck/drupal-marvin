<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\Config\ConfigInterface;
use Sweetchuck\Utils\FileSystemUtils;
use Symfony\Component\Console\Helper\Table as ConsoleTable;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Yaml\Yaml;

class Utils {

  /**
   * Drupal related composer package types.
   *
   * @var array<string, bool>
   */
  public array $drupalPackageTypes = [
    'drupal-core' => TRUE,
    'drupal-drush' => TRUE,
    'drupal-module' => TRUE,
    'drupal-profile' => TRUE,
    'drupal-theme' => TRUE,
  ];

  /**
   * @var array<string, bool>
   */
  public array $drupalPhpExtensions = [
    'engine' => TRUE,
    'install' => TRUE,
    'module' => TRUE,
    'profile' => TRUE,
    'theme' => TRUE,
    'php' => TRUE,
    'inc' => TRUE,
  ];

  public function __construct(
    protected FileSystemUtils $fileSystemUtils,
  ) {
  }

  /**
   * @deprecated
   *
   * @todo https://packagist.org/packages/mindplay/composer-locator
   */
  public function marvinRootDir(): string {
    return Path::getDirectory(__DIR__);
  }

  /**
   * Combination of \JSON_* flags to make a dump human-readable.
   */
  public function getJsonEncodeFlags(): int {
    return \JSON_UNESCAPED_UNICODE
      | \JSON_UNESCAPED_SLASHES
      | \JSON_PRETTY_PRINT;
  }

  public function isFilePath(string $uri): bool {
    $uri = trim($uri);
    if ($uri === '') {
      return FALSE;
    }

    if (str_starts_with($uri, '/')
      || str_starts_with($uri, '.')
    ) {
      return TRUE;
    }

    $parts = parse_url($uri);
    if (!isset($parts['scheme'])
      || $parts['scheme'] === 'file'
    ) {
      return TRUE;
    }

    // Windows "C:\" or "C:/".
    if (preg_match('@^[a-zA-Z]:(\\|/)@', $uri) === 1) {
      return TRUE;
    }

    // @todo Problem is that, \realpath() returns FALSE for non-existing paths.
    return (bool) realpath($uri);
  }

  /**
   * @todo This is not bullet proof, but good enough.
   * @todo Cache.
   * @todo Without $drushConfig.
   */
  public function getProjectRootDir(ConfigInterface $drushConfig): string {
    $cwd = $drushConfig->get('env.cwd');
    $composerFileName = $this->getComposerJsonFileName();
    if ($cwd && file_exists("$cwd/$composerFileName")) {
      return $cwd;
    }

    $vendorDir = $drushConfig->get('drush.vendor-dir');

    return $this->fileSystemUtils->findFileUpward($composerFileName, $vendorDir);
  }

  /**
   * @return array<string>
   */
  public function drupalPhpExtensionPatterns(): array {
    return $this->prefixSuffixItems(array_keys($this->drupalPhpExtensions, TRUE), '*.');
  }

  /**
   * @phpstan-param array<string> $items
   *
   * @return array<string>
   */
  public function prefixSuffixItems(array $items, string $prefix = '', string $suffix = ''): array {
    return array_map(
      function ($value) use ($prefix, $suffix) {
        return "{$prefix}{$value}{$suffix}";
      },
      $items,
    );
  }

  /**
   * Checks that a composer package is Drupal related or not.
   *
   * @phpstan-param marvin-composer-info $package
   *   Composer package definition.
   */
  public function isDrupalPackage(array $package): bool {
    $type = $package['type'] ?? 'library';

    return !empty($this->drupalPackageTypes[$type]);
  }

  public function makeRelativePathToComposerBinDir(
    string $fromDirectory,
    string $projectRootDir,
    string $composerBinDir,
  ): string {
    if ($fromDirectory === '.') {
      return './' . $composerBinDir;
    }

    return Path::makeRelative(
      Path::join($projectRootDir, $composerBinDir),
      $fromDirectory,
    );
  }

  /**
   * @todo Use sweetchuck/env-var-storage or similar.
   *
   * @see \Composer\Factory::getComposerFile
   */
  public function getComposerJsonFileName(): string {
    $name = 'COMPOSER';

    if (array_key_exists($name, $_SERVER)) {
      return (string) $_SERVER[$name];
    }

    if (array_key_exists($name, $_ENV)) {
      return (string) $_ENV[$name];
    }

    return getenv($name) ?: 'composer.json';
  }

  /**
   * @deprecated
   *
   * @see \Sweetchuck\Utils\FileSystemUtils::findFileUpward
   */
  public function findFileUpward(string $fileName, string $absoluteDirectory = ''): string {
    throw new \BadMethodCallException('Deprecated.');
  }

  /**
   * @return array<string>
   */
  public function getDirectDescendantDrupalPhpFiles(string $dir): array {
    $extensions = [];
    foreach (array_keys($this->drupalPhpExtensions, TRUE) as $extension) {
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

  public function escapeYamlValueString(string $text): string {
    return rtrim(mb_substr(Yaml::dump(['a' => $text]), 3));
  }

  /**
   * Inserts or updates the version number, preserves the comments.
   *
   * @todo Dedicated "Version Number Updater" service.
   */
  public function changeVersionNumberInInfoYaml(string $yamlString, string $versionNumber): string {
    // Yaml::parse() and Yaml::dump() strips the comments.
    $escapedVersionNumber = $this->escapeYamlValueString($versionNumber);

    $value = Yaml::parse($yamlString);
    if (array_key_exists('version', $value)) {
      // @todo This does not work with "version: |" and "version: >".
      return preg_replace(
        '/(?<=version: ).+/um',
        $escapedVersionNumber,
        $yamlString,
      );
    }

    $this->ensureTrailingEol($yamlString);

    return $yamlString . "version: $escapedVersionNumber\n";
  }

  /**
   * @todo Preserve comments.
   */
  public function changeVersionNumberInLibrariesYaml(string $yamlString, string $versionNumber): string {
    $data = Yaml::parse($yamlString);
    foreach ($data as &$library) {
      $library = ['version' => $versionNumber] + $library;
    }

    $yamlString = Yaml::dump($data, 99, 2);
    $this->ensureTrailingEol($yamlString);

    return $yamlString;
  }

  public function ensureTrailingEol(string &$text): void {
    if (!preg_match('/[\r\n]$/u', $text)) {
      $text .= "\n";
    }
  }

  /**
   * @deprecated
   *
   * @todo Probably this method is not necessary any more.
   * @todo Rename to stringUpperCamel().
   */
  public function phpUnitSuiteNameToNamespace(string $suitName): string {
    return (new UnicodeString("a_$suitName"))
      ->camel()
      ->trimPrefix('a')
      ->toString();
  }

  /**
   * @deprecated
   *
   * @param array<\Consolidation\AnnotatedCommand\CommandError> $commandErrors
   */
  public function aggregateCommandErrors(array $commandErrors): ?CommandError {
    $errorCode = 0;
    $messages = [];
    foreach ($commandErrors as $commandError) {
      $messages[] = $commandError->getOutputData();
      $errorCode = max($errorCode, $commandError->getExitCode());
    }

    if ($messages) {
      return new CommandError(implode(PHP_EOL, $messages), $errorCode);
    }

    return NULL;
  }

  public function formatTextBySeverity(int $severity, string $text): string {
    return match ($severity) {
      RfcLogLevel::EMERGENCY,
      RfcLogLevel::ALERT,
      RfcLogLevel::CRITICAL,
      RfcLogLevel::ERROR => "<fg=red>$text</>",
      RfcLogLevel::WARNING => "<fg=yellow>$text</>",
      default => $text,
    };
  }

  /**
   * @return array<string>
   */
  public function getGitHookNames(): array {
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

  /**
   * @phpstan-return marvin-composer-package-name-parts
   */
  public function splitPackageName(string $packageName): array {
    $parts = explode('/', $packageName, 2);
    if (count($parts) === 1) {
      // Fake it from "my_module_01" to "drupal/my_module_01".
      array_unshift($parts, 'drupal');
    }

    return [
      'vendor' => $parts[0],
      'name' => $parts[1],
    ];
  }

  /**
   * @deprecated Use \Sweetchuck\Utils\VersionNumber.
   *
   * @return string
   *   Example: "080327".
   */
  public function phpVersionToPhpVersionId(string $phpVersion): string {
    if (mb_strpos($phpVersion, '.') === FALSE) {
      return mb_strlen($phpVersion) % 2 === 0
        ? $phpVersion
        : "0{$phpVersion}";
    }

    $phpVersionParts = explode('.', $phpVersion) + [1 => 0, 2 => 0];

    return sprintf(
      '%02d%02d%02d',
      $phpVersionParts[0],
      $phpVersionParts[1],
      $phpVersionParts[2]
    );
  }

  public function phpErrorAll(string $phpVersion): int {
    $phpVersionMajorMinor = mb_substr($this->phpVersionToPhpVersionId($phpVersion), 0, 4);

    return match ($phpVersionMajorMinor) {
      '0803',
      '0804' => 32767,
      '0805' => 30719,
      default => \E_ALL,
    };
  }

  /**
   * @phpstan-param marvin-db-connection $connection
   *
   * @todo Use \Sweetchuck\Utils\StringUtils::buildUri.
   * @todo Use PHP 8.5 \Uri\Rfc3986\Uri.
   */
  public function dbUrl(array $connection): string {
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
   * @param array<string> $dirs
   * @param array<string> $files
   *
   * @phpstan-return null|marvin-first-file
   */
  public function pickFirstFile(array $dirs, array $files): ?array {
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

  public function getTriStateCliOption(?bool $state, string $optionName): string {
    if ($state === NULL) {
      return '';
    }

    return $state
      ? "--$optionName"
      : "--no-$optionName";
  }

  /**
   * @phpstan-param null|marvin-rfc-log-level $severity
   * @phpstan-param marvin-rfc-log-level $lowestError
   *
   * @phpstan-return int<0, 8>
   */
  public function getExitCodeBasedOnSeverity(?int $severity, int $lowestError = RfcLogLevel::ERROR): int {
    return $severity === NULL || $severity > $lowestError
      ? 0
      : $severity + 1;
  }

  /**
   * @return array<string>
   */
  public function explodeCommaSeparatedList(string $items): array {
    return array_filter(
      preg_split('/\s*,\s*/', trim($items)) ?: [],
      static fn (string $item): bool => $item !== '',
    );
  }

  /**
   * @phpstan-param iterable<string, marvin-task-definition> $taskDefinitions
   */
  public function taskDefinitionsAsTable(iterable $taskDefinitions, OutputInterface $output): ConsoleTable {
    $table = new ConsoleTable($output);
    $table->setHeaders([
      'Weight',
      'Name',
      'Provider',
      'Description',
    ]);
    foreach ($taskDefinitions as $taskDefinition) {
      $table->addRow([
        'weight' => new TableCell(
          (string) ($taskDefinition['weight'] ?? 0),
          [],
        ),
        'name' => $taskDefinition['name'] ?? '',
        'provider' => $taskDefinition['provider'] ?? '',
        'description' => $taskDefinition['description'] ?? '',
      ]);
    }

    return $table;
  }

  public function callableToString(callable $callable): string {
    if (is_string($callable)) {
      return $callable;
    }

    if (is_array($callable)) {
      $class = is_string($callable[0])
        ? $callable[0]
        : get_class($callable[0]);

      return "$class::{$callable[1]}";
    }

    if (is_object($callable)) {
      return get_class($callable) . '::__invoke';
    }

    // @todo \Closure or something is wrong.
    return '';
  }

  /**
   * @deprecated Use \Symfony\Component\Filesystem\Filesystem::readFile().
   */
  public function fileGetContents(string $fileName): string {
    $content = file_get_contents($fileName);
    if ($content === FALSE) {
      throw new \Exception("$fileName could not be open");
    }

    return $content;
  }

  /**
   * @phpstan-param null|int<1, max> $length
   */
  public function generateHashSalt(?int $length = NULL): string {
    if ($length === NULL) {
      $length = random_int(32, 64);
    }

    return bin2hex(random_bytes($length));
  }

  /**
   * @phpstan-return array<string, string>
   */
  public function stringVariants(string $string, string $prefix, string $suffix = ''): array {
    return [
      "{$prefix}Snake{$suffix}" => (new UnicodeString($string))
        ->snake()
        ->toString(),
      "{$prefix}UpperCamel{$suffix}" => (new UnicodeString("a_$string"))
        ->camel()
        ->trimPrefix('a')
        ->toString(),
      "{$prefix}LowerCamel{$suffix}" => (new UnicodeString($string))
        ->camel()
        ->toString(),
    ];
  }

  /**
   * @return array<string, mixed>
   */
  public function getConditionContext(ConfigInterface $drushConfig): array {
    return [
      // @todo Detect file watcher.
      'watch' => NULL,
      'gitHookName' => $drushConfig->get('marvin.gitHookName'),
      // @todo Detect CI.
      'ci' => NULL,
    ];
  }

  /**
   * @param array<string, mixed> $items
   * @param array<string, mixed> $context
   *
   * @todo Dedicated service.
   */
  public function getFirstByConditions(array $items, array $context): ?string {
    foreach ($items as $id => $item) {
      if (empty($item['conditions'])) {
        return (string) $id;
      }

      foreach ($item['conditions'] as $name => $value) {
        if (!array_key_exists($name, $context)) {
          // @todo Error handling.
          continue;
        }

        if ($context[$name] !== $value) {
          continue 2;
        }
      }

      return (string) $id;
    }

    return NULL;
  }

}

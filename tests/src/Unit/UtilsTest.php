<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit;

use Consolidation\AnnotatedCommand\CommandError;
use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\Utils;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sweetchuck\Utils\FileSystemUtils;
use Symfony\Component\Filesystem\Path;

#[Group('marvin')]
#[CoversClass(Utils::class)]
class UtilsTest extends TestCase {

  protected function createInstance(): Utils {
    return new Utils(new FileSystemUtils());
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesIsDrupalPackage(): array {
    return [
      'module' => [TRUE, ['type' => 'drupal-module']],
      'theme' => [TRUE, ['type' => 'drupal-theme']],
      'profile' => [TRUE, ['type' => 'drupal-profile']],
      'drush' => [TRUE, ['type' => 'drupal-drush']],
      'core' => [TRUE, ['type' => 'drupal-core']],
      'library' => [FALSE, ['type' => 'library']],
    ];
  }

  /**
   * @phpstan-param marvin-composer-info $package
   */
  #[Test]
  #[DataProvider('casesIsDrupalPackage')]
  public function testIsDrupalPackage(bool $expected, array $package): void {
    static::assertSame(
      $expected,
      $this->createInstance()->isDrupalPackage($package),
    );
  }

  #[Test]
  public function testGetComposerJsonFileName(): void {
    $this->setBackupStaticProperties(TRUE);

    $utils = $this->createInstance();

    unset(
      $_SERVER['COMPOSER'],
      $_ENV['COMPOSER'],
    );
    putenv('COMPOSER');
    static::assertSame('composer.json', $utils->getComposerJsonFileName());

    putenv('COMPOSER=composer.json');
    static::assertSame('composer.json', $utils->getComposerJsonFileName());

    putenv('COMPOSER=composer.foo.json');
    static::assertSame('composer.foo.json', $utils->getComposerJsonFileName());
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesDrupalPhpExtensionPatterns(): array {
    return [
      'basic' => [
        [
          '*.engine',
          '*.install',
          '*.module',
          '*.profile',
          '*.theme',
          '*.php',
          '*.inc',
        ],
      ],
    ];
  }

  #[Test]
  #[DataProvider('casesDrupalPhpExtensionPatterns')]
  public function testDrupalPhpExtensionPatterns(mixed $expected): void {
    static::assertSame(
      $expected,
      $this->createInstance()->drupalPhpExtensionPatterns(),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetDirectDescendantDrupalPhpFiles(): array {
    return [
      'empty' => [
        [],
        '',
        [],
      ],
      'basic' => [
        [
          'a.engine',
          'a.install',
          'a.module',
          'a.profile',
          'a.theme',
          'a.php',
          'a.inc',
        ],
        'a',
        [
          'a' => [
            'a.engine' => '',
            'a.install' => '',
            'a.module' => '',
            'a.profile' => '',
            'a.theme' => '',
            'a.php' => '',
            'a.inc' => '',
          ],
        ],
      ],
    ];
  }

  /**
   * @phpstan-param array<string, mixed> $vfsStructure
   */
  #[Test]
  #[DataProvider('casesGetDirectDescendantDrupalPhpFiles')]
  public function testGetDirectDescendantDrupalPhpFiles(mixed $expected, string $relativeDirectory, array $vfsStructure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $absoluteDirectory = Path::join($vfs->url(), $relativeDirectory);

    static::assertSame(
      $expected,
      $this->createInstance()->getDirectDescendantDrupalPhpFiles($absoluteDirectory),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesEscapeYamlValueString(): array {
    return [
      'basic' => ["' foo bar '", ' foo bar '],
    ];
  }

  #[Test]
  #[DataProvider('casesEscapeYamlValueString')]
  public function testEscapeYamlValueString(string $expected, string $text): void {
    static::assertSame(
      $expected,
      $this->createInstance()->escapeYamlValueString($text),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesChangeVersionNumberInInfoYaml(): array {
    return [
      'first' => [
        'expected' => <<<YAML
          version: 4.5.6
          type: module
          name: My Module

          YAML,
        'yamlString' => <<<'YAML'
          version: 1.2.3
          type: module
          name: My Module

          YAML,
        'versionNumber' => '4.5.6',
      ],
      'middle' => [
        'expected' => <<<YAML
          type: module
          version: 4.5.6
          name: My Module

          YAML,
        'yamlString' => <<<'YAML'
          type: module
          version: 1.2.3
          name: My Module

          YAML,
        'versionNumber' => '4.5.6',
      ],
      'last-eol-yes' => [
        'expected' => <<<YAML
          type: module
          name: My Module
          version: 4.5.6

          YAML,
        'yamlString' => <<<'YAML'
          type: module
          name: My Module
          version: 1.2.3

          YAML,
        'versionNumber' => '4.5.6',
      ],
      'last-eol-no' => [
        'expected' => <<<YAML
          type: module
          name: My Module
          version: 4.5.6
          YAML,
        'yamlString' => <<<'YAML'
          type: module
          name: My Module
          version: 1.2.3
          YAML,
        'versionNumber' => '4.5.6',
      ],
      'only-eol-yes' => [
        'expected' => <<<YAML
          version: 4.5.6

          YAML,
        'yamlString' => <<<'YAML'
          version: 1.2.3

          YAML,
        'versionNumber' => '4.5.6',
      ],
      'only-eol-no' => [
        'expected' => <<<YAML
          version: 4.5.6
          YAML,
        'yamlString' => <<<'YAML'
          version: 1.2.3
          YAML,
        'versionNumber' => '4.5.6',
      ],
      'missing-eol-yes' => [
        'expected' => <<<YAML
          type: module
          name: My Module
          version: 4.5.6

          YAML,
        'yamlString' => <<<'YAML'
          type: module
          name: My Module

          YAML,
        'versionNumber' => '4.5.6',
      ],
      'missing-eol-no' => [
        'expected' => <<<YAML
          type: module
          name: My Module
          version: 4.5.6

          YAML,
        'yamlString' => <<<'YAML'
          type: module
          name: My Module
          YAML,
        'versionNumber' => '4.5.6',
      ],
    ];
  }

  #[Test]
  #[DataProvider('casesChangeVersionNumberInInfoYaml')]
  public function testChangeVersionNumberInInfoYaml(string $expected, string $yamlString, string $versionNumber): void {
    static::assertSame(
      $expected,
      $this->createInstance()->changeVersionNumberInInfoYaml($yamlString, $versionNumber),
    );
  }

  /**
   * @return array<string, mixed>
   */
  public static function casesChangeVersionNumberInLibrariesYaml(): array {
    return [
      'basic' => [
        'expected' => <<< 'YAML'
          a:
            version: 4.5.6
            css:
              component:
                a.css: {  }
          b:
            version: 4.5.6
            css:
              component:
                a.css: {  }

          YAML,
        'yamlString' => <<< 'YAML'
          a:
            version: 1.0.x-dev
            css:
              component:
                a.css: { }
          b:
            css:
              component:
                a.css: { }
            version: 1.0.x-dev
          YAML,
        'versionNumber' => '4.5.6',
      ],
    ];
  }

  #[Test]
  #[DataProvider('casesChangeVersionNumberInLibrariesYaml')]
  public function testChangeVersionNumberInLibrariesYaml(
    string $expected,
    string $yamlString,
    string $versionNumber,
  ): void {
    $subject = $this->createInstance();
    static::assertSame(
      $expected,
      $subject->changeVersionNumberInLibrariesYaml($yamlString, $versionNumber),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesEnsureTrailingEol(): array {
    return [
      'missing' => ['a' . PHP_EOL, 'a'],
      'already there' => ['a' . PHP_EOL, 'a' . PHP_EOL],
    ];
  }

  #[Test]
  #[DataProvider('casesEnsureTrailingEol')]
  public function testEnsureTrailingEol(string $expected, string $text): void {
    $this->createInstance()->ensureTrailingEol($text);
    static::assertSame($expected, $text);
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesPhpUnitSuiteNameToNamespace(): array {
    return [
      'unit' => ['Unit', 'unit'],
      'kernel' => ['Kernel', 'kernel'],
      'functional' => ['Functional', 'functional'],
      'functional-javascript' => [
        'FunctionalJavascript',
        'functional-javascript',
      ],
    ];
  }

  #[Test]
  #[DataProvider('casesPhpUnitSuiteNameToNamespace')]
  public function testPhpUnitSuiteNameToNamespace(mixed $expected, string $suiteName): void {
    static::assertSame(
      $expected,
      $this->createInstance()->phpUnitSuiteNameToNamespace($suiteName),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesAggregateCommandErrors(): array {
    return [
      'empty' => [
        NULL,
        [],
      ],
      'basic' => [
        [
          'exitCode' => 3,
          'outputData' => implode(PHP_EOL, ['a', 'b', 'c']),
        ],
        [
          new CommandError('a', 1),
          new CommandError('b', 3),
          new CommandError('c', 2),
        ],
      ],
    ];
  }

  /**
   * @phpstan-param null|array{exitCode: string, outputData: string} $expected
   * @phpstan-param \Consolidation\AnnotatedCommand\CommandError[] $commandErrors
   */
  #[Test]
  #[DataProvider('casesAggregateCommandErrors')]
  public function testAggregateCommandErrors(?array $expected, array $commandErrors): void {
    $utils = $this->createInstance();
    $commandError = $utils->aggregateCommandErrors($commandErrors);
    if ($commandError === NULL) {
      static::assertSame($expected, $commandError);

      return;
    }

    static::assertSame($expected['exitCode'], $commandError->getExitCode());
    static::assertSame($expected['outputData'], $commandError->getOutputData());
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesFormatTextBySeverity(): array {
    return [
      'warning' => ['<fg=yellow>a</>', RfcLogLevel::WARNING, 'a'],
      'error' => ['<fg=red>b</>', RfcLogLevel::ERROR, 'b'],
      'unknown' => ['c', 42, 'c'],
    ];
  }

  #[Test]
  #[DataProvider('casesFormatTextBySeverity')]
  public function testFormatTextBySeverity(string $expected, int $severity, string $text): void {
    static::assertSame(
      $expected,
      $this->createInstance()->formatTextBySeverity($severity, $text),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesDbUrl(): array {
    return [
      'sqlite - basic' => [
        'sqlite:///default__default.sqlite',
        [
          'driver' => 'sqlite',
          'database' => '/default__default.sqlite',
        ],
      ],
      'mysql - full' => [
        'mysql://a:b@c:d/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'password' => 'b',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-port;' => [
        'mysql://a:b@c/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'password' => 'b',
          'host' => 'c',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-password;' => [
        'mysql://a@c:d/e#f',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
          'prefix' => 'f',
        ],
      ],
      'mysql - no-prefix;' => [
        'mysql://a@c:d/e',
        [
          'driver' => 'mysql',
          'username' => 'a',
          'host' => 'c',
          'port' => 'd',
          'database' => 'e',
        ],
      ],
      'mysql - no-all;' => [
        'mysql://c/e',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
        ],
      ],
      'mysql - prefix array default;' => [
        'mysql://c/e#f',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
          'prefix' => ['default' => 'f'],
        ],
      ],
      'mysql - prefix array no-default;' => [
        'mysql://c/e',
        [
          'driver' => 'mysql',
          'host' => 'c',
          'database' => 'e',
          'prefix' => ['foo' => 'f'],
        ],
      ],
    ];
  }

  /**
   * @phpstan-param marvin-db-connection $connection
   */
  #[Test]
  #[DataProvider('casesDbUrl')]
  public function testDbUrl(mixed $expected, array $connection): void {
    static::assertSame(
      $expected,
      $this->createInstance()->dbUrl($connection),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesSplitPackageName(): array {
    return [
      'basic' => [
        [
          'vendor' => 'a',
          'name' => 'b',
        ],
        'a/b',
      ],
      'only name' => [
        [
          'vendor' => 'drupal',
          'name' => 'b',
        ],
        'b',
      ],
    ];
  }

  #[Test]
  #[DataProvider('casesSplitPackageName')]
  public function testSplitPackageName(mixed $expected, string $packageName): void {
    static::assertSame(
      $expected,
      $this->createInstance()->splitPackageName($packageName),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesPhpVersionToPhpVersionId(): array {
    return [
      'empty' => ['', ''],
      '070102' => ['070102', '070102'],
      '0.0.0' => ['000000', '0.0.0'],
      '7.2.3' => ['070203', '7.2.3'],
    ];
  }

  #[Test]
  #[DataProvider('casesPhpVersionToPhpVersionId')]
  public function testPhpVersionToPhpVersionId(string $expected, string $phpVersion): void {
    static::assertSame(
      $expected,
      $this->createInstance()->phpVersionToPhpVersionId($phpVersion),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesPhpErrorAll(): array {
    return [
      'v8.3' => [32767, '8.3'],
      'v803' => [32767, '803'],
      'v8.4' => [32767, '8.4'],
      'v804' => [32767, '804'],
      'v8.5' => [30719, '8.5'],
      'v805' => [30719, '805'],
      '????' => [\E_ALL, '???'],
    ];
  }

  #[Test]
  #[DataProvider('casesPhpErrorAll')]
  public function testPhpErrorAll(int $expected, string $phpVersion): void {
    static::assertSame(
      $expected,
      $this->createInstance()->phpErrorAll($phpVersion),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesPickFirstFile(): array {
    return [
      'empty' => [NULL, [], [], []],
      'not-exists' => [
        NULL,
        ['a'],
        ['b.txt'],
        [
          'a' => [
            'b.md' => '',
          ],
        ],
      ],
      'exists in current' => [
        [
          'dir' => '.',
          'file' => 'a.txt',
        ],
        ['.'],
        ['a.txt'],
        [
          'a.txt' => '',
        ],
      ],
      'exists' => [
        [
          'dir' => 'b',
          'file' => 'b.txt',
        ],
        ['c', 'b', 'a', '.'],
        ['b.txt', 'a.txt'],
        [
          'a' => [
            'a.txt' => '',
          ],
          'b' => [
            'a.txt' => '',
            'b.txt' => '',
          ],
          'c' => [
            'a.md' => '',
          ],
          'a.txt' => '',
          'b.txt' => '',
        ],
      ],
    ];
  }

  /**
   * @phpstan-param null|array<string, mixed> $expected
   * @phpstan-param array<string> $dirs
   * @phpstan-param array<string> $files
   * @phpstan-param array<string, mixed> $vfsStructure
   */
  #[Test]
  #[DataProvider('casesPickFirstFile')]
  public function testPickFirstFile(?array $expected, array $dirs, array $files, array $vfsStructure): void {
    $vfs = vfsStream::setup(
      __FUNCTION__ . '.' . $this->dataName(),
      NULL,
      $vfsStructure
    );

    $dirPrefix = $vfs->url();

    if ($expected) {
      $expected['dir'] = Path::join($dirPrefix, $expected['dir']);
    }

    foreach (array_keys($dirs) as $key) {
      $dirs[$key] = Path::join($dirPrefix, $dirs[$key]);
    }

    static::assertSame(
      $expected,
      $this->createInstance()->pickFirstFile($dirs, $files),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetTriStateCliOption(): array {
    return [
      'null' => ['', NULL, 'a'],
      'true' => ['--a', TRUE, 'a'],
      'false' => ['--no-a', FALSE, 'a'],
    ];
  }

  #[Test]
  #[DataProvider('casesGetTriStateCliOption')]
  public function testGetTriStateCliOption(string $expected, ?bool $state, string $optionName): void {
    static::assertSame(
      $expected,
      $this->createInstance()->getTriStateCliOption($state, $optionName),
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public static function casesGetExitCodeBasedOnSeverity(): array {
    return [
      'basic' => [0, RfcLogLevel::WARNING],
      'lowest error level' => [5, RfcLogLevel::WARNING, RfcLogLevel::WARNING],
    ];
  }

  /**
   * @phpstan-param int<0, 7> ...$args
   */
  #[Test]
  #[DataProvider('casesGetExitCodeBasedOnSeverity')]
  public function testGetExitCodeBasedOnSeverity(mixed $expected, int ...$args): void {
    static::assertSame(
      $expected,
      $this->createInstance()->getExitCodeBasedOnSeverity(...$args),
    );
  }

  #[Test]
  public function testGenerateHashSalt(): void {
    $subject = $this->createInstance();

    $hash01 = $subject->generateHashSalt();
    static::assertGreaterThanOrEqual(32 * 2, strlen($hash01));
    static::assertLessThanOrEqual(64 * 2, strlen($hash01));

    $hash02 = $subject->generateHashSalt();
    static::assertGreaterThanOrEqual(32 * 2, strlen($hash02));
    static::assertLessThanOrEqual(64 * 2, strlen($hash02));

    static::assertNotEquals($hash01, $hash02);

    $hash03 = $subject->generateHashSalt(42);
    static::assertSame(84, strlen($hash03));
  }

}

<?php

namespace Drupal\Dev\marvin\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use DrupalComposer\DrupalScaffold\Handler as DrupalScaffoldHandler;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlCodeCoverageReporter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Sweetchuck\GitHooks\Composer\Scripts as GitHooks;
use Sweetchuck\Utils\Filter\ArrayFilterFileSystemExists;

class Scripts {

  /**
   * Composer event callback.
   */
  public static function postInstallCmd(Event $event): int {
    $self = new static($event);

    $self
      ->gitHooksDeploy()
      ->phpcsConfigSet()
      ->preparePhpunitXml()
      ->prepareProject();

    return 0;
  }

  /**
   * Composer event callback.
   */
  public static function postUpdateCmd(Event $event): int {
    $self = new static($event);

    $self
      ->gitHooksDeploy()
      ->phpcsConfigSet()
      ->preparePhpunitXml()
      ->prepareProject();

    return 0;
  }

  public static function generateCoverageHtml(Event $event): int {
    $self = new static($event);

    $srcFiles = (new Finder())
      ->in('reports/machine/coverage-php')
      ->files()
      ->name('*.php');

    $dstDir = 'reports/human/coverage/all/html';

    (new HtmlCodeCoverageReporter())->process(
      $self->mergeCodeCoverageFiles($srcFiles),
      $dstDir
    );

    return 0;
  }

  /**
   * Current event.
   *
   * @var \Composer\Script\Event
   */
  protected $event;

  /**
   * CLI process callback.
   *
   * @var \Closure
   */
  protected $processCallbackWrapper;

  /**
   * @var string
   */
  protected $projectRoot = 'tests/fixtures/project_01';

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Current working directory.
   *
   * @var string
   */
  protected $cwd = '.';

  protected function __construct(Event $event, ?LoggerInterface $logger = NULL, ?Filesystem $fs = NULL, string $cwd = '.') {
    $this->cwd = $cwd ?: '.';
    $this->event = $event;
    $this->logger = $logger ?: $this->createLogger();
    $this->fs = $fs ?: $this->createFilesystem();
  }

  protected function createLogger(): LoggerInterface {
    $io = $this->event->getIO();
    if ($io instanceof LoggerInterface) {
      return $io;
    }

    $verbosity = OutputInterface::VERBOSITY_NORMAL;
    if ($io->isDebug()) {
      $verbosity = OutputInterface::VERBOSITY_DEBUG;
    }
    elseif ($io->isVeryVerbose()) {
      $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE;
    }
    elseif ($io->isVerbose()) {
      $verbosity = OutputInterface::VERBOSITY_VERBOSE;
    }

    $output = new ConsoleOutput($verbosity, $io->isDecorated());

    return new ConsoleLogger($output);
  }

  protected function createFilesystem(): Filesystem {
    return new Filesystem();
  }

  /**
   * @return $this
   */
  protected function initProcessCallbackWrapper() {
    if (!$this->processCallbackWrapper) {
      $this->processCallbackWrapper = function (string $type, string $buffer) {
        $this->processCallback($type, $buffer);
      };
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function gitHooksDeploy() {
    if ($this->event->isDevMode()) {
      GitHooks::deploy($this->event);
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function phpcsConfigSet() {
    /** @var \Composer\Config $config */
    $config = $this->event->getComposer()->getConfig();

    $phpcsExecutable = $config->get('bin-dir') . '/phpcs';
    if (!$this->fs->exists($phpcsExecutable)) {
      $this->logger->info("phpcs executable not exists: '$phpcsExecutable'");

      return $this;
    }

    $rulesDir = $config->get('vendor-dir') . '/drupal/coder/coder_sniffer';
    if (!$this->fs->exists($rulesDir)) {
      $this->logger->info("phpcs rules not exists: '$rulesDir'");

      return $this;
    }

    $cmdPattern = '%s --config-set installed_paths %s';
    $cmdArgs = [
      escapeshellcmd($phpcsExecutable),
      escapeshellcmd($rulesDir),
    ];

    $this->processRun('.', vsprintf($cmdPattern, $cmdArgs));

    return $this;
  }

  /**
   * @return $this
   */
  protected function preparePhpunitXml() {
    /** @var \Composer\Config $config */
    $config = $this->event->getComposer()->getConfig();

    $phpunitExecutable = $config->get('bin-dir') . '/phpunit';
    if (!$this->fs->exists($phpunitExecutable)) {
      $this->logger->info('PHPUnit configuration file creation is skipped because phpunit/phpunit is not installed');

      return $this;
    }

    $dstFileName = "{$this->cwd}/phpunit.xml";
    if ($this->fs->exists($dstFileName)) {
      $this->logger->info('PHPUnit configuration file is already exists');

      return $this;
    }

    $srcFileName = "{$this->cwd}/phpunit.xml.dist";
    if (!$this->fs->exists($srcFileName)) {
      $this->logger->info("File not exists: '$srcFileName'");

      return $this;
    }

    $basePattern = '<env name="%s" value="%s"/>';
    $replacementPairs = [];
    foreach ($this->getPhpunitEnvVars() as $envVarName => $envVarValue) {
      $placeholder = sprintf("<!-- $basePattern -->", $envVarName, '');
      $replacementPairs[$placeholder] = sprintf($basePattern, $envVarName, $this->escapeXmlAttribute($envVarValue));
    }

    $content = $this->fileGetContents($srcFileName);
    $this->fs->dumpFile($dstFileName, strtr($content, $replacementPairs));

    return $this;
  }

  /**
   * @return $this
   */
  protected function prepareProject() {
    if (!$this->event->isDevMode()) {
      return $this;
    }

    $this
      ->prepareProjectComposerJson()
      ->prepareProjectSelf()
      ->prepareProjectDirs()
      ->prepareProjectScaffold()
      ->prepareProjectSettingsPhp();

    return $this;
  }

  /**
   * @return $this
   */
  protected function prepareProjectComposerJson() {
    $content = [
      'name' => 'drupal/marvin-tests-project_01',
      'description' => 'drupal/marvin-tests-project_01',
      "license" => "proprietary",
      'type' => 'drupal-project',
      'extra' => [
        'installer-types' => [
          'bower-asset',
          'npm-asset',
        ],
        'installer-paths' => [
          'docroot/core' => [
            'type:drupal-core',
          ],
          'docroot/libraries/{$name}' => [
            'type:drupal-library',
            'type:bower-asset',
            'type:npm-asset',
          ],
          'docroot/modules/contrib/{$name}' => [
            'type:drupal-module',
          ],
          'docroot/profiles/contrib/{$name}' => [
            'type:drupal-profile',
          ],
          'docroot/themes/contrib/{$name}' => [
            'type:drupal-theme',
          ],
          'drush/Commands/contrib/{$name}' => [
            'type:drupal-drush',
          ],
        ],
        'enable-patching' => TRUE,
        'composer-exit-on-patch-failure' => TRUE,
        'patches' => [],
        'drupal-scaffold' => [
          'excludes' => [
            'sites/example.settings.local.php',
            '.csslintrc',
            '.editorconfig',
            '.eslintignore',
            '.eslintrc.json',
            '.gitattributes',
            '.ht.router.php',
            'web.config',
          ],
          'initial' => [
            'sites/default/default.services.yml' => 'sites/default/services.yml',
            'sites/default/default.settings.php' => 'sites/default/settings.php',
          ],
        ],
      ],
    ];

    $this->fs->dumpFile(
      "{$this->cwd}/{$this->projectRoot}/composer.json",
      json_encode($content, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    );

    return $this;
  }

  /**
   * @return $this
   */
  protected function prepareProjectSelf() {
    $dstDir = $this->getProjectSelfDestination();

    $relative = implode(
      '/',
      array_fill(
        0,
        substr_count($dstDir, '/') + 1,
        '..'
      )
    );

    $filesToSymlink = $this->getProjectSelfFilesToSymlink();
    $this->fs->mkdir($dstDir);
    foreach ($filesToSymlink as $fileToSymlink) {
      $this->fs->symlink("$relative/$fileToSymlink", "$dstDir/$fileToSymlink");
    }

    return $this;
  }

  protected function prepareProjectDirs() {
    $drushSutRoot = $this->projectRoot;

    $dirs = [
      "$drushSutRoot/docroot/libraries",
      "$drushSutRoot/docroot/profiles",
      "$drushSutRoot/docroot/themes",
    ];
    $this->fs->mkdir($dirs, 0777 - umask());

    return $this;
  }

  /**
   * @return $this
   */
  protected function prepareProjectScaffold() {
    $indexPhp = $this->projectRoot . '/docroot/index.php';
    $io = $this->event->getIO();
    if ($this->fs->exists("{$this->cwd}/$indexPhp")) {
      $io->write(
        "File '<info>$indexPhp</info>' already exists.",
        IOInterface::VERBOSE
      );

      return $this;
    }

    $handler = new DrupalScaffoldHandler($this->event->getComposer(), $io);
    $handler->downloadScaffold();
    $handler->generateAutoload();

    return $this;
  }

  protected function prepareProjectSettingsPhp() {
    $src = "{$this->projectRoot}/docroot/sites/default/default.settings.php";
    if (!$this->fs->exists($src)) {
      $this->logger->info(
        "File not exists: {fileName}",
        [
          'fileName' => $src,
        ]
      );

      return $this;
    }

    $dst = "{$this->projectRoot}/docroot/sites/default/settings.php";
    if ($this->fs->exists($dst)) {
      $this->logger->info(
        "File already exists: {fileName}",
        [
          'fileName' => $dst,
        ]
      );

      return $this;
    }

    $replacementPairs = [];
    $replacementPairs['$databases = [];'] = <<<'PHP'
$databases = [
  'default' => [
    'default' => [
      'driver' => 'sqlite',
      'namespace' => '\Drupal\Core\Database\Driver\sqlite',
      'database' => __DIR__ . '/db.default.default.sqlite',
    ],
  ],
];
PHP;

    $this->fs->dumpFile($dst, strtr($this->fileGetContents($src), $replacementPairs));

    return $this;
  }

  protected function getProjectSelfDestination(): string {
    return "{$this->projectRoot}/drush/custom/" . $this->getComposerPackageName();
  }

  protected function getComposerPackageName(): string {
    $parts = explode('/', $this->event->getComposer()->getPackage()->getName(), 2);
    if (empty($parts[1])) {
      throw new Exception('Invalid package name', 1);
    }

    return $parts[1];
  }

  /**
   * @return string[]
   */
  protected function getProjectSelfFilesToSymlink(): array {
    $extra = $this->event->getComposer()->getPackage()->getExtra();
    $filesToSymLink = $extra['marvin']['drushUnish']['filesToSymlink'] ?? [];
    $filesToSymLink += $this->getProjectSelfFilesToSymlinkDefaults();

    $filesToSymLink = array_keys($filesToSymLink, TRUE, TRUE);

    $filter = new ArrayFilterFileSystemExists();
    $filter->setBaseDir($this->cwd);

    return array_filter($filesToSymLink, $filter);
  }

  /**
   * @return bool[]
   */
  protected function getProjectSelfFilesToSymlinkDefaults(): array {
    return [
      'Commands' => TRUE,
      'src' => TRUE,
      'composer.json' => TRUE,
      'drush9.services.yml' => TRUE,
      'drush.services.yml' => TRUE,
    ];
  }

  protected function processRun(string $workingDirectory, string $command): Process {
    $this->event->getIO()->write("Run '$command' in '$workingDirectory'");
    $process = new Process($command, NULL, NULL, NULL, 0);
    $process->setWorkingDirectory($workingDirectory);
    $process->run($this->processCallbackWrapper);

    return $process;
  }

  protected function processCallback(string $type, string $buffer): void {
    $type === Process::OUT ?
      $this->event->getIO()->write($buffer, FALSE)
      : $this->event->getIO()->writeError($buffer, FALSE);
  }

  protected function escapeXmlAttribute(string $value): string {
    return htmlentities($value, ENT_QUOTES);
  }

  protected function fileGetContents(string $fileName): string {
    $content = file_get_contents($fileName);
    if ($content === FALSE) {
      throw new RuntimeException("File '$fileName' is not readable.", 1);
    }

    return $content;
  }

  protected function getPhpunitEnvVars(): array {
    return [
      'SIMPLETEST_BASE_URL' => 'http://localhost:8888',
      'DTT_BASE_URL' => 'http://localhost:8888',
      'SIMPLETEST_DB' => "sqlite://sites/default/db.default.default.sqlite",
      'UNISH_DB_URL' => 'sqlite://sites/default/db.default.default.sqlite',
      'BROWSERTEST_OUTPUT_DIRECTORY' => realpath($this->cwd) . "/{$this->projectRoot}/docroot/sites/simpletest/browser_output",
    ];
  }

  protected function mergeCodeCoverageFiles(iterable $files): CodeCoverage {
    $codeCoverage = new CodeCoverage();

    $coverage = NULL;
    $require = function (string $fileName) {
      return require $fileName;
    };

    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($files as $file) {
      $coverage = $require($file->getRealPath());
      if ($coverage instanceof CodeCoverage) {
        $codeCoverage->merge($coverage);
      }
    }

    return $codeCoverage;
  }

}

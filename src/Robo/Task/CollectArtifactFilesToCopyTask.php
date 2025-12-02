<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\FinderBuilder;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;
use Sweetchuck\Utils\Filter\EnabledFilter;
use Sweetchuck\Utils\Filter\FileSystemExistsFilter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CollectArtifactFilesToCopyTask extends BaseTask implements StateAwareInterface {

  use StateAwareTrait;

  protected string $taskName = 'Marvin - Collect artifact files to copy';

  protected string $composerJsonFileName = 'composer.json';

  public function getComposerJsonFileName(): string {
    return $this->composerJsonFileName;
  }

  public function setComposerJsonFileName(string $value): static {
    $this->composerJsonFileName = $value;

    return $this;
  }

  protected string $packagePath = '.';

  public function getPackagePath(): string {
    return $this->packagePath;
  }

  public function setPackagePath(string $value): static {
    $this->packagePath = $value;

    return $this;
  }

  protected string $buildDir = '';

  public function getBuildDir(): string {
    return $this->buildDir;
  }

  public function setBuildDir(string $buildDir): static {
    $this->buildDir = $buildDir;

    return $this;
  }

  protected array $filesToCopyDefinitions;

  public function getFilesToCopyDefinitions(): array {
    return $this->filesToCopyDefinitions;
  }

  public function setFilesToCopyDefinitions(array $filesToCopyDefinitions): static {
    $this->filesToCopyDefinitions = $filesToCopyDefinitions;

    return $this;
  }

  protected Filesystem $fs;

  protected FinderBuilder $finderBuilder;

  public function __construct(
    ?Filesystem $fs,
    ?FinderBuilder $finderBuilder,
  ) {
    $this->fs = $fs ?? new Filesystem();
    $this->finderBuilder = $finderBuilder ?? new FinderBuilder();
  }

  protected function getStateKey(): string {
    return 'filesToCopy';
  }

  /**
   * @phpstan-param marvin-robo-task-artifact-collect-files-options $options
   */
  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('composerJsonFileName', $options)) {
      $this->setComposerJsonFileName($options['composerJsonFileName']);
    }

    if (array_key_exists('packagePath', $options)) {
      $this->setPackagePath($options['packagePath']);
    }

    if (array_key_exists('buildDir', $options)) {
      $this->setBuildDir($options['buildDir']);
    }

    if (array_key_exists('filesToCopy', $options)) {
      $this->setFilesToCopyDefinitions($options['filesToCopy']);
    }

    return $this;
  }

  protected function runHeader(): static {
    $this->printTaskInfo($this->getPackagePath());

    return $this;
  }

  protected function runAction(): static {
    $stateKey = $this->getStateKey();
    $state = $this->getState();

    // @todo Decide that if it should be there and throw an error.
    if (!isset($state[$stateKey])) {
      $state[$stateKey] = new Finder();
    }
    /** @var \Symfony\Component\Finder\Finder $mainFinder */
    $mainFinder = $state[$stateKey];

    $filesToCopyDefinitions = array_filter(
      $this->getFilesToCopyDefinitions(),
      new EnabledFilter(),
    );
    foreach ($filesToCopyDefinitions as $definition) {
      if (!isset($definition['src']['in'])) {
        $definition['src']['in'] = '.';
      }

      if (!$this->fs->exists($definition['src']['in'])) {
        // @todo Maybe a warning is enough.
        throw new \RuntimeException(
          sprintf(
            'Source directory not exists: "%s"',
            $definition['src']['in'],
          ),
        );
      }

      $subFinder = new Finder();
      $this->finderBuilder->build($definition['src'], $subFinder);
      $mainFinder->append($subFinder);
    }

    return $this;
  }

  protected function runActionOld(): static {
    // @todo The configuration what to copy should come from outside instead of
    // the hard-coded file patterns.
    // @todo Add extra exclude dirs configuration.
    $artifactDir = $this->getBuildDir() ?: 'artifact';
    $artifactDirSafe = preg_quote($artifactDir, '@');

    $packagePath = $this->getPackagePath();
    $composerInfo = ComposerInfo::create($packagePath, $this->getComposerJsonFileName());

    switch ($composerInfo['type']) {
      case 'project':
      case 'drupal-project':
        $docroot = $composerInfo->getDrupalRootDir();
        $docrootSafe = preg_quote($docroot, '@');

        $mainFiles = new Finder();
        $this->assets['files'] = $mainFiles;

        $outerSitesDir = 'sites';
        $outerSitesDirSafe = preg_quote($outerSitesDir, '@');

        $files = (new Finder())
          ->in($packagePath)
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^{$docrootSafe}/sites/simpletest/@")
          ->name('*.yml')
          ->name('*.twig')
          ->files();

        $dirs = [
          "$docrootSafe/modules",
          "$docrootSafe/themes",
          "$docrootSafe/profiles",
          "$docrootSafe/libraries",
          "$docrootSafe/sites/[^/]+/modules",
          "$docrootSafe/sites/[^/]+/themes",
          "$docrootSafe/sites/[^/]+/profiles",
          "$docrootSafe/sites/[^/]+/libraries",
          'drush/Commands',
          "$docrootSafe/sites/[^/]+/drush/Commands",
        ];
        foreach ($dirs as $dir) {
          $files
            ->path("@^$dir/custom/@")
            ->notPath("@$dir/custom/[^/]+/node_modules/@");
        }

        $this
          ->configFinderGit($files)
          ->configFinderPhp($files)
          ->configFinderCss($files, FALSE)
          ->configFinderJavaScript($files)
          ->configFinderImages($files)
          ->configFinderFont($files)
          ->configFinderOs($files)
          ->configFinderIde($files);
        $mainFiles->append($files);

        $files = (new Finder())
          ->in($packagePath)
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^{$docrootSafe}/sites/simpletest/@")
          ->path("@^$docrootSafe/sites/[^/]+/@")
          ->name('settings.php')
          ->name('services.yml')
          ->files();
        $mainFiles->append($files);

        $files = (new Finder())
          ->in($packagePath)
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^{$docrootSafe}/sites/simpletest/@")
          ->path("@^{$outerSitesDirSafe}/[^/]+/translations/@")
          ->path("@^{$outerSitesDirSafe}/[^/]+/config/@")
          ->ignoreDotFiles(FALSE)
          ->files();
        $mainFiles->append($files);

        $files = (new Finder())
          ->in($packagePath)
          ->path("@^drush/@")
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^drush/Commands/@")
          ->name('*.yml')
          ->notName('drush.local.example.yml')
          ->notName('drush.local.yml')
          ->files();
        $mainFiles->append($files);

        $files = new Finder();
        $files->in("$packagePath");
        $files
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^{$docrootSafe}/sites/simpletest/@")
          ->path('@^patches/@')
          ->name('*.patch')
          ->files();
        $mainFiles->append($files);

        $files = new \ArrayIterator(array_filter(
          [
            'composer.json',
            'composer.lock',
            "{$docroot}/autoload.php",
            "{$docroot}/index.php",
            "$docroot/.htaccess",
            "$docroot/favicon.ico",
            "$docroot/robots.txt",
            "$outerSitesDir/all/assets/robots-additions.txt",
          ],
          (new FileSystemExistsFilter())->setBaseDir($packagePath),
        ));
        $mainFiles->append($files);
        break;

      case 'drupal-profile':
      case 'drupal-module':
      case 'drupal-theme':
      case 'drupal-drush':
        // @todo Exclude the "artifactDir" only if it is inside the "packagePath".
        $files = (new Finder())
          ->in($packagePath)
          ->files()
          ->notPath("@^{$artifactDirSafe}/@")
          ->name('composer.json')
          ->name('*.md')
          ->name('*.yml')
          ->name('*.twig');

        $this
          ->configFinderGit($files)
          ->configFinderPhp($files)
          ->configFinderCss($files, TRUE)
          ->configFinderJavaScript($files)
          ->configFinderTypeScript($files)
          ->configFinderImages($files)
          ->configFinderFont($files)
          ->configFinderRuby($files)
          ->configFinderDocker($files)
          ->configFinderCi($files)
          ->configFinderOs($files)
          ->configFinderIde($files);

        if ($composerInfo['type'] === 'drupal-profile') {
          // These directories probably are in the DRUPAL_ROOT directory.
          $files
            ->notPath('modules/contrib')
            ->notPath('libraries/contrib')
            ->notPath('themes/contrib');
        }

        $this->assets['files'][] = $files;
        break;
    }

    return $this;
  }

  protected function configFinderGit(Finder $finder): static {
    $finder
      ->notPath('.git')
      ->notPath('.gtm')
      ->notName('.gitignore');

    return $this;
  }

  protected function configFinderPhp(Finder $finder): static {
    $finder
      ->name('*.php')
      ->name('*.inc')
      ->name('*.install')
      ->name('*.module')
      ->name('*.theme')
      ->name('*.profile')
      ->name('*.engine')
      ->notPath('vendor')
      ->notName('.phpbrewrc')
      ->notName('composer.lock')
      ->notName('phpcs.xml.dist')
      ->notName('phpcs.xml')
      ->notName('phpunit.xml.dist')
      ->notName('phpunit.xml');

    return $this;
  }

  protected function configFinderCss(Finder $finder, bool $withImportable): static {
    $finder
      ->name('*.css')
      ->notPath('.sass-cache')
      ->notName('config.rb')
      ->notName('.sass-lint.yml')
      ->notName('sass-lint.yml')
      ->notName('.scss-lint.yml')
      ->notName('scss-lint.yml')
      ->notName('*.css.map');

    if ($withImportable) {
      $finder
        ->name('/^_.+\.scss$/')
        ->name('/^_.+\.sass$/');
    }

    return $this;
  }

  protected function configFinderJavaScript(Finder $finder): static {
    $finder
      ->name('*.js')
      ->notPath('node_modules')
      ->notName('.npmignore')
      ->notName('*.js.map')
      ->notName('npm-debug.log')
      ->notName('npm-shrinkwrap.json')
      ->notName('package.json')
      ->notName('yarn.lock')
      ->notName('yarn-error.log')
      ->notName('.nvmrc')
      ->notName('.eslintignore')
      ->notName('.eslintrc.json')
      ->notName('bower.json')
      ->notName('.bowerrc')
      ->notName('Gruntfile.js')
      ->notName('gulpfile.js')
      ->notName('.istanbul.yml');

    return $this;
  }

  protected function configFinderTypeScript(Finder $finder): static {
    $finder
      ->name('*.td.ts')
      ->notPath('typings')
      ->notName('typings.json')
      ->notName('tsconfig.json')
      ->notName('tsd.json')
      ->notName('tslint.json');

    return $this;
  }

  protected function configFinderImages(Finder $finder): static {
    $finder
      ->name('*.png')
      ->name('*.jpeg')
      ->name('*.jpg')
      ->name('*.svg')
      ->name('*.ttf')
      ->name('*.ico');

    return $this;
  }

  protected function configFinderFont(Finder $finder): static {
    $finder
      ->name('*.otf')
      ->name('*.woff')
      ->name('*.woff2')
      ->name('*.eot');

    return $this;
  }

  protected function configFinderRuby(Finder $finder): static {
    $finder
      ->notPath('.bundle')
      ->notName('.ruby-version')
      ->notName('.ruby-gemset')
      ->notName('.rvmrc')
      ->notName('Gemfile')
      ->notName('Gemfile.lock');

    return $this;
  }

  protected function configFinderDocker(Finder $finder): static {
    $finder
      ->notName('Dockerfile')
      ->notName('docker-compose.yml')
      ->notName('.dockerignore');

    return $this;
  }

  protected function configFinderCi(Finder $finder): static {
    $finder
      ->notName('Jenkinsfile')
      ->notPath('.gitlab')
      ->notName('.gitlab-ci.yml')
      ->notPath('.github')
      ->notName('.travis.yml')
      ->notPath('.circle')
      ->notName('circle.yml');

    return $this;
  }

  protected function configFinderOs(Finder $finder): static {
    $finder
      ->notName('.directory')
      ->notName('.directory.lock.*.test')
      ->notName('.DS_Store')
      ->notName('._*');

    return $this;
  }

  protected function configFinderIde(Finder $finder): static {
    $finder
      ->notPath('.idea')
      ->notPath('.phpstorm.meta.php')
      ->notName('.phpstorm.meta.php')
      ->notName('*___jb_old___')
      ->notPath('.kdev4')
      ->notName('*.kdev4')
      ->notName('.kdev*')
      ->notName('cifs*')
      ->notName('*~')
      ->notName('.*.kate-swp')
      ->notName('.kateconfig')
      ->notName('.kateproject')
      ->notPath('.kateproject.d')
      ->notName('*.loalize')
      ->notPath('nbproject')
      ->notPath('.settings')
      ->notName('.buildpath')
      ->notName('.project')
      ->notName('.*.swp')
      ->notName('.phing_targets')
      ->notName('nohup.out')
      ->notName('.~lock.*');

    return $this;
  }

}

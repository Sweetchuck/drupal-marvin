<?php

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\ComposerInfo;
use Sweetchuck\Utils\Filter\ArrayFilterFileSystemExists;
use Symfony\Component\Finder\Finder;

class ArtifactCollectFilesTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Collect files to release';

  /**
   * @var string
   */
  protected $composerJsonFileName = 'composer.json';

  public function getComposerJsonFileName(): string {
    return $this->composerJsonFileName;
  }

  /**
   * @return $this
   */
  public function setComposerJsonFileName(string $value) {
    $this->composerJsonFileName = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $packagePath = '.';

  public function getPackagePath(): string {
    return $this->packagePath;
  }

  /**
   * @return $this
   */
  public function setPackagePath(string $value) {
    $this->packagePath = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $artifactDir = '';

  public function getArtifactDir(): string {
    return $this->artifactDir;
  }

  /**
   * @return $this
   */
  public function setArtifactDir(string $artifactDir) {
    $this->artifactDir = $artifactDir;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('composerJsonFileName', $options)) {
      $this->setComposerJsonFileName($options['composerJsonFileName']);
    }

    if (array_key_exists('packagePath', $options)) {
      $this->setPackagePath($options['packagePath']);
    }

    if (array_key_exists('artifactDir', $options)) {
      $this->setArtifactDir($options['artifactDir']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runHeader() {
    $this->printTaskInfo($this->getPackagePath());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    // @todo The configuration what to copy should come from outside instead of
    // the hard-coded file patterns.
    // @todo Add extra exclude dirs configuration.
    $artifactDir = $this->getArtifactDir() ?: 'artifact';
    $artifactDirSafe = preg_quote($artifactDir, '@');

    $packagePath = $this->getPackagePath();
    $composerInfo = ComposerInfo::create($packagePath, $this->getComposerJsonFileName());

    switch ($composerInfo['type']) {
      case 'project':
      case 'drupal-project':
        $docroot = $composerInfo->getDrupalRootDir();
        $docrootSafe = preg_quote($docroot, '@');

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
        $this->assets['files'][] = $files;

        $this->assets['files'][] = (new Finder())
          ->in($packagePath)
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^{$docrootSafe}/sites/simpletest/@")
          ->path("@$docrootSafe/sites/[^/]+/@")
          ->name('settings.php')
          ->name('services.yml')
          ->files();

        $files = (new Finder())
          ->in($packagePath)
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^{$docrootSafe}/sites/simpletest/@")
          ->path("@^{$outerSitesDirSafe}/[^/]+/translations/@")
          ->path("@^{$outerSitesDirSafe}/[^/]+/config/@")
          ->ignoreDotFiles(FALSE)
          ->files();
        $this->assets['files'][] = $files;

        $this->assets['files'][] = (new Finder())
          ->in($packagePath)
          ->path("@^drush/@")
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^drush/Commands/@")
          ->name('*.yml')
          ->notName('drush.local.example.yml')
          ->notName('drush.local.yml')
          ->files();

        $this->assets['files'][] = (new Finder)
          ->in($packagePath)
          ->notPath("@^{$artifactDirSafe}@")
          ->notPath("@^{$docrootSafe}/sites/simpletest/@")
          ->path('@^patches/@')
          ->name('*.patch')
          ->files();
        $this->assets['files'][] = 'composer.json';
        $this->assets['files'][] = 'composer.lock';
        $this->assets['files'][] = "{$docroot}/autoload.php";
        $this->assets['files'][] = "{$docroot}/index.php";

        $this->assets['files'] = array_merge(
          $this->assets['files'],
          array_filter(
            [
              "$docroot/.htaccess",
              "$docroot/favicon.ico",
              "$docroot/robots.txt",
            ],
            (new ArrayFilterFileSystemExists())->setBaseDir($packagePath)
          )
        );
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

  /**
   * @return $this
   */
  protected function configFinderGit(Finder $finder) {
    $finder
      ->notPath('.git')
      ->notPath('.gtm')
      ->notName('.gitignore');

    return $this;
  }

  /**
   * @return $this
   */
  protected function configFinderPhp(Finder $finder) {
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

  /**
   * @return $this
   */
  protected function configFinderCss(Finder $finder, bool $withImportable) {
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

  /**
   * @return $this
   */
  protected function configFinderJavaScript(Finder $finder) {
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

  /**
   * @return $this
   */
  protected function configFinderTypeScript(Finder $finder) {
    $finder
      ->name('*.td.ts')
      ->notPath('typings')
      ->notName('typings.json')
      ->notName('tsconfig.json')
      ->notName('tsd.json')
      ->notName('tslint.json');

    return $this;
  }

  /**
   * @return $this
   */
  protected function configFinderImages(Finder $finder) {
    $finder
      ->name('*.png')
      ->name('*.jpeg')
      ->name('*.jpg')
      ->name('*.svg')
      ->name('*.ttf')
      ->name('*.ico');

    return $this;
  }

  /**
   * @return $this
   */
  protected function configFinderFont(Finder $finder) {
    $finder
      ->name('*.otf')
      ->name('*.woff')
      ->name('*.woff2')
      ->name('*.eot');

    return $this;
  }

  /**
   * @return $this
   */
  protected function configFinderRuby(Finder $finder) {
    $finder
      ->notPath('.bundle')
      ->notName('.ruby-version')
      ->notName('.ruby-gemset')
      ->notName('.rvmrc')
      ->notName('Gemfile')
      ->notName('Gemfile.lock');

    return $this;
  }

  /**
   * @return $this
   */
  protected function configFinderDocker(Finder $finder) {
    $finder
      ->notName('Dockerfile')
      ->notName('docker-compose.yml')
      ->notName('.dockerignore');

    return $this;
  }

  /**
   * @return $this
   */
  protected function configFinderCi(Finder $finder) {
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

  /**
   * @return $this
   */
  protected function configFinderOs(Finder $finder) {
    $finder
      ->notName('.directory')
      ->notName('.directory.lock.*.test')
      ->notName('.DS_Store')
      ->notName('._*');

    return $this;
  }

  /**
   * @return $this
   */
  protected function configFinderIde(Finder $finder) {
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

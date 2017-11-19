<?php

namespace Drush\marvin\Robo\Task;

use Drush\marvin\ComposerInfo;
use Symfony\Component\Finder\Finder;

class ReleaseCollectFilesTask extends BaseTask {

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
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    foreach ($options as $name => $value) {
      switch ($name) {
        case 'composerJsonFileName':
          $this->setComposerJsonFileName($value);
          break;

        case 'packagePath':
          $this->setPackagePath($value);
          break;

      }
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
    // @todo Add extra exclude dirs configuration.
    // This task should be independent from the $this->getConfig().
    $buildDir = $this->getConfig()->get('command.marvin.settings.buildDir');

    $packagePath = $this->getPackagePath();
    $composerInfo = ComposerInfo::create($this->getComposerJsonFileName(), $packagePath);
    switch ($composerInfo['type']) {
      case 'project':
      case 'drupal-project':
        // @todo
        break;

      case 'drupal-profile':
      case 'drupal-module':
      case 'drupal-theme':
      case 'drupal-drush':
        $files = (new Finder())
          ->in($packagePath)
          ->files()
          ->notPath($buildDir)
          ->name('composer.json')
          ->name('*.md')
          ->name('*.yml')
          ->name('*.twig');

        $this
          ->configFinderGit($files)
          ->configFinderPhp($files)
          ->configFinderCss($files)
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

        $this->assets['files'] = $files;
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
  protected function configFinderCss(Finder $finder) {
    $finder
      ->name('*.css')
      ->name('/^_.+\.scss$/')
      ->name('/^_.+\.sass$/')
      ->notPath('.sass-cache')
      ->notName('config.rb')
      ->notName('.scss-lint.yml')
      ->notName('*.css.map');

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

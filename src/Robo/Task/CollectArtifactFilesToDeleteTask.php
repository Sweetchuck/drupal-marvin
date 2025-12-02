<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\FinderBuilder;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;
use Sweetchuck\Utils\Filter\EnabledFilter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CollectArtifactFilesToDeleteTask extends BaseTask implements StateAwareInterface {

  use StateAwareTrait;

  protected string $taskName = 'Marvin - Collect artifact files to delete';

  protected string $buildDir = '';

  public function getBuildDir(): string {
    return $this->buildDir;
  }

  public function setBuildDir(string $buildDir): static {
    $this->buildDir = $buildDir;

    return $this;
  }

  protected array $filesToDeleteDefinitions;

  public function getFilesToDeleteDefinitions(): array {
    return $this->filesToDeleteDefinitions;
  }

  public function setFilesToDeleteDefinitions(array $filesToDeleteDefinitions): static {
    $this->filesToDeleteDefinitions = $filesToDeleteDefinitions;

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
    return 'filesToDelete';
  }

  /**
   * @phpstan-param marvin-robo-task-artifact-collect-files-to-delete-options $options
   */
  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('buildDir', $options)) {
      $this->setBuildDir($options['buildDir']);
    }

    if (array_key_exists('filesToDeleteDefinitions', $options)) {
      $this->setFilesToDeleteDefinitions($options['filesToDeleteDefinitions']);
    }

    return $this;
  }

  protected function runHeader(): static {
    $this->printTaskInfo($this->getBuildDir());

    return $this;
  }

  protected function runAction(): static {
    $stateKey = $this->getStateKey();
    $state = $this->getState();
    /** @var \Symfony\Component\Finder\Finder $mainFinder */
    $mainFinder = $state[$stateKey];

    $buildDir = $this->getBuildDir();
    $finderDefinitions = array_filter(
      $this->getFilesToDeleteDefinitions(),
      new EnabledFilter(),
    );
    $replacements = [
      '{{ buildDir }}' => $buildDir,
    ];
    foreach ($finderDefinitions as $definition) {
      if (!isset($definition['src']['in'])) {
        $definition['src']['in'] = '{{ buildDir }}';
      }
      $definition['src']['in'] = strtr($definition['src']['in'], $replacements);

      if (!$this->fs->exists($definition['src']['in'])) {
        // @todo Maybe a warning is enough.
        throw new \RuntimeException(
          sprintf(
            'Source directory not exists: "%s"',
            $definition['src']['in'],
          ),
        );
      }

      $files = new Finder();
      $this->finderBuilder->build($definition['src'], $files);
      $mainFinder->append($files);
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

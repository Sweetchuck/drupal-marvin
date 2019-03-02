<?php

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait;
use Drush\Drush;
use Drupal\marvin\CommandDelegatorTrait;
use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Utils;
use Drush\Log\Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Tasks;
use Stringy\StaticStringy;
use Sweetchuck\Robo\Composer\ComposerTaskLoader;
use Webmozart\PathUtil\Path;

class CommandsBase extends Tasks implements
    ConfigAwareInterface,
    CustomEventAwareInterface,
    LoggerAwareInterface {

  // @todo Almost every ConfigAwareTrait method is overwritten. Custom trait?
  // @todo Those methods that are not part of the ConfigAwareInterface only used
  // in consolidation/robo tests.
  use ConfigAwareTrait;
  use LoggerAwareTrait;
  use ComposerTaskLoader;
  use CommandDelegatorTrait;
  use CustomEventAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected static function configPrefix() {
    return 'marvin.';
  }

  /**
   * @var \Drupal\marvin\ComposerInfo
   */
  protected $composerInfo;

  public function __construct(?ComposerInfo $composerInfo = NULL) {
    $this->composerInfo = $composerInfo;
  }

  /**
   * To complete \Psr\Log\LoggerInterface.
   */
  public function getLogger(): LoggerInterface {
    if (!$this->logger) {
      $this->logger = new Logger($this->output());
    }

    return $this->logger;
  }

  protected function initComposerInfo() {
    if (!$this->composerInfo) {
      $this->composerInfo = ComposerInfo::create($this->getProjectRootDir());
    }

    return $this;
  }

  protected function getComposerInfo(): ComposerInfo {
    return $this
      ->initComposerInfo()
      ->composerInfo;
  }

  protected static function getClassKey(string $key): string {
    $configPrefix = static::configPrefix();
    $configClass = Utils::commandClassNameToConfigIdentifier(get_called_class());
    $configPostFix = static::configPostfix();

    $classKey = sprintf('%s%s%s.%s', $configPrefix, $configClass, $configPostFix, $key);
    $classKey = preg_replace('/\.{2,}/', '.', $classKey);

    return rtrim($classKey, '.');
  }

  protected static function configName(): string {
    return static::getClassKey('');
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigValue($key, $default = NULL) {
    $config = $this->getConfig();

    return $config ? $config->get(static::getClassKey($key), $default) : $default;
  }

  /**
   * @todo This is not bullet proof, but good enough.
   * @todo Cache.
   */
  protected function getProjectRootDir(): string {
    // This method called from the __constructor() and the $this->config is not
    // initialized yet.
    // @todo Find a better way to initialize the $this->composerInfo.
    $config = $this->getConfig() ?: Drush::config();
    $vendorDir = $config->get('drush.vendor-dir');

    return Utils::findFileUpward(Utils::getComposerJsonFileName(), $vendorDir);
  }

  protected function makeRelativePathToComposerBinDir(string $fromDirectory): string {
    $composerInfo = $this->getComposerInfo();

    if ($fromDirectory === '.') {
      return './' . $composerInfo['config']['bin-dir'];
    }

    $projectRoot = $this->getProjectRootDir();

    return Path::makeRelative(
      Path::join($projectRoot, $composerInfo['config']['bin-dir']),
      $fromDirectory
    );
  }

  protected function getEnvironment(): string {
    return getenv('DRUSH_MARVIN_ENVIRONMENT') ?:
      $this->getConfig()->get('marvin.environment', 'dev');
  }

  /**
   * @return string[]
   */
  protected function getEnvironmentVariants(): array {
    $config = $this->getConfig();
    $environment = $this->getEnvironment();
    $gitHook = $config->get('marvin.gitHook');
    $ci = $environment === 'ci' ? $config->get('marvin.ci') : '';

    $environmentVariants = [];

    $modifiers = array_filter([$environment, $ci, $gitHook]);
    while ($modifiers) {
      $environmentVariants[] = StaticStringy::camelize(implode('-', $modifiers));
      array_pop($modifiers);
    }

    $environmentVariants[] = 'default';

    return $environmentVariants;
  }

  protected function getGitExecutable(): string {
    return $this
      ->getConfig()
      ->get('marvin.gitExecutable', 'git');
  }

  /**
   * @return bool|null
   */
  protected function getTriStateOptionValue(string $optionName): ?bool {
    if ($this->input()->getOption($optionName)) {
      return TRUE;
    }

    if ($this->input()->getOption("no-$optionName")) {
      return FALSE;
    }

    return NULL;
  }

}

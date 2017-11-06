<?php

namespace Drush\Commands\Marvin;

use Drush\marvin\ComposerInfo;
use Drush\marvin\Utils;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Console\Input\ArrayInput;
use Webmozart\PathUtil\Path;

class CommandsBase extends Tasks implements ConfigAwareInterface {

  // @todo Almost every ConfigAwareTrait method is overwritten. Custom trait?
  // @todo Those methods that are not part of the ConfigAwareInterface only used
  // in consolidation/robo tests.
  use ConfigAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected static function configPrefix() {
    return 'command.';
  }

  /**
   * @var \Drush\marvin\ComposerInfo
   */
  protected $composerInfo;

  public function __construct() {
    $this->composerInfo = ComposerInfo::create();
  }

  protected static function getClassKey(string $key): string {
    $configPrefix = static::configPrefix();
    $configClass = Utils::commandClassNameToConfigIdentifier(get_called_class());
    $configPostFix = static::configPostfix();

    $classKey = sprintf('%s%s%s.%s', $configPrefix, $configClass, $configPostFix, $key);

    return rtrim($classKey, '.');
  }

  protected static function configName(): string {
    return static::getClassKey('');
  }

  /**
   * {@inheritdoc}
   */
  public static function configure($key, $value, $config = NULL) {
    if (!$config) {
      $config = Robo::config();
    }

    $config->setDefault(static::getClassKey($key), $value);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigValue($key, $default = NULL) {
    $config = $this->getConfig();

    return $config ? $config->get(static::getClassKey($key), $default) : $default;
  }

  protected function makeRelativePathToComposerBinDir(string $fromDirectory): string {
    return Path::makeRelative($this->composerInfo['config']['bin-dir'], $fromDirectory);
  }

  protected function invokeCommand(string $commandName, array $arguments = []): \Closure {
    return function () use ($commandName, $arguments) {
      /** @var \Drush\Application $app */
      $app = $this->getContainer()->get('application');
      $input = new ArrayInput($arguments);

      return $app
        ->find($commandName)
        ->run($input, $this->output());
    };
  }

}

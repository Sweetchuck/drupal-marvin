<?php

namespace Drush\Commands\Marvin;

use Drush\marvin\ComposerInfo;
use Drush\marvin\Robo\ManagedDrupalExtensionTaskLoader;
use Drush\marvin\Utils;
use Robo\Collection\CollectionBuilder;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Robo;
use Robo\Tasks;
use Stringy\StaticStringy;
use Sweetchuck\Robo\Composer\ComposerTaskLoader;
use Symfony\Component\Console\Input\ArrayInput;
use Webmozart\PathUtil\Path;

class CommandsBase extends Tasks implements ConfigAwareInterface {

  // @todo Almost every ConfigAwareTrait method is overwritten. Custom trait?
  // @todo Those methods that are not part of the ConfigAwareInterface only used
  // in consolidation/robo tests.
  use ConfigAwareTrait;
  use ComposerTaskLoader;
  use ManagedDrupalExtensionTaskLoader;

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

  /**
   * @return string[]
   */
  protected function getEnvironmentVariants(): array {
    $config = $this->getConfig();
    $environment = $config->get('command.marvin.settings.environment');
    $gitHook = $config->get('command.marvin.settings.gitHook');

    $environmentVariants = [];
    if ($environment === 'dev' && $gitHook) {
      $environmentVariants[] = StaticStringy::camelize("$environment-$gitHook");
    }
    $environmentVariants[] = $environment;
    $environmentVariants[] = 'default';

    return $environmentVariants;
  }

  protected function getTaskManagedDrupalExtensionList(): CollectionBuilder {
    return $this
      ->collectionBuilder()
      ->addTask($this->taskComposerPackagePaths())
      ->addTask(
        $this
          ->taskManagedDrupalExtensionList()
          ->deferTaskConfiguration('setPackagePaths', 'packagePaths'));
  }

  protected function getManagedDrupalExtensions(): array {
    $result = $this
      ->getTaskManagedDrupalExtensionList()
      ->run()
      ->stopOnFail();

    return $result['managedDrupalExtensions'];
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

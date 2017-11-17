<?php

namespace Drush\Commands\marvin;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\marvin\ArrayUtils\FilterArrayUtils;
use Drush\marvin\ComposerInfo;
use Drush\marvin\Robo\ManagedDrupalExtensionTaskLoader;
use Drush\marvin\Utils;
use Robo\Collection\CollectionBuilder;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Robo\Tasks;
use Stringy\StaticStringy;
use Sweetchuck\Robo\Composer\ComposerTaskLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
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
  protected function getConfigValue($key, $default = NULL) {
    $config = $this->getConfig();

    return $config ? $config->get(static::getClassKey($key), $default) : $default;
  }

  /**
   * @return $this
   */
  protected function hookOptionAddArgumentPackages(Command $command) {
    if ($this->isIncubatorProject()
      && !$command->getDefinition()->hasArgument('packages')
    ) {
      $command->addArgument(
        'packages',
        InputArgument::IS_ARRAY,
        'Filesystem path or machine-name of any kind of Drupal extension.'
      );
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function hookValidateArgumentPackages(CommandData $commandData) {
    if ($this->isIncubatorProject()) {
      $packageNames = $commandData->input()->getArgument('packages');
      $packages = [];
      $invalidPackageNames = [];
      foreach ($packageNames as $packageName) {
        $package = $this->normalizeManagedDrupalExtensionName($packageName);
        if ($package) {
          $packages[] = $package['name'];
        }
        else {
          $invalidPackageNames[] = $packageName;
        }
      }

      if ($invalidPackageNames) {
        // @todo Designed exit codes and messages.
        throw new \Exception(
          'The following packages are invalid: ' . implode(', ', $invalidPackageNames),
          1
        );
      }

      if (!$packages) {
        $packages = array_keys($this->getManagedDrupalExtensions());
      }

      $commandData->input()->setArgument('packages', $packages);
    }

    return $this;
  }

  protected function makeRelativePathToComposerBinDir(string $fromDirectory): string {
    if ($fromDirectory === '.') {
      return './' . $this->composerInfo['config']['bin-dir'];
    }

    $projectRoot = $this->getConfig()->get('env.cwd');

    return Path::makeRelative(
      Path::join($projectRoot, $this->composerInfo['config']['bin-dir']),
      $fromDirectory
    );
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
    $packageDefinitions = (array) $this
      ->getConfig()
      ->get('command.marvin.settings.managedDrupalExtension.package');
    $ignoredPackages = FilterArrayUtils::filterEnabled($packageDefinitions, 'ignored', FALSE);

    return $this
      ->collectionBuilder()
      ->addTask($this->taskComposerPackagePaths())
      ->addTask(
        $this
          ->taskManagedDrupalExtensionList()
          ->setIgnoredPackages(array_keys($ignoredPackages))
          ->deferTaskConfiguration('setPackagePaths', 'packagePaths'));
  }

  /**
   * @var null|array
   */
  protected $managedDrupalExtensions = NULL;

  protected function getManagedDrupalExtensions(): array {
    if ($this->managedDrupalExtensions === NULL) {
      $result = $this
        ->getTaskManagedDrupalExtensionList()
        ->run()
        ->stopOnFail();

      $this->managedDrupalExtensions = $result['managedDrupalExtensions'];
    }

    return $this->managedDrupalExtensions;
  }

  protected function normalizeManagedDrupalExtensionName(string $extensionName): ?array {
    $managedDrupalExtensions = $this->getManagedDrupalExtensions();

    // Fully qualified composer package name.
    if (isset($managedDrupalExtensions[$extensionName])) {
      return [
        'name' => $extensionName,
        'path' => $managedDrupalExtensions[$extensionName],
      ];
    }

    // Transform a Drupal extension machine-name to a fq composer package name.
    if (mb_strpos($extensionName, '/') === FALSE) {
      $packageName = "drupal/$extensionName";
      if (isset($managedDrupalExtensions[$packageName])) {
        return [
          'name' => $packageName,
          'path' => $managedDrupalExtensions[$packageName],
        ];
      }
    }

    // Full real path.
    $packageName = array_search($extensionName, $managedDrupalExtensions);
    if ($packageName !== FALSE) {
      return [
        'name' => $packageName,
        'path' => $extensionName,
      ];
    }

    // Relative path.
    if (is_dir($extensionName)) {
      $packagePath = realpath($extensionName);
      $packageName = array_search($packagePath, $managedDrupalExtensions);
      if ($packageName !== FALSE) {
        return [
          'name' => $packageName,
          'path' => $packagePath,
        ];
      }
    }

    return NULL;
  }

  protected function isIncubatorProject(): bool {
    return in_array($this->composerInfo['type'], ['project', 'drupal-project'])
      && $this->getConfig()->get('command.marvin.settings.projectType') === 'incubator';
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

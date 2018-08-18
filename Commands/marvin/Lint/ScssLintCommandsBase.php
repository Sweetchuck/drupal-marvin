<?php

namespace Drush\Commands\marvin\Lint;

use Drupal\marvin\Robo\ScssLintConfigFallbackTaskLoader;
use Drupal\marvin\Utils;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;
use Sweetchuck\Robo\Bundler\BundlerTaskLoader;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\Rvm\RvmTaskLoader;
use Sweetchuck\Robo\ScssLint\ScssLintTaskLoader;
use Webmozart\PathUtil\Path;

class ScssLintCommandsBase extends CommandsBase {

  use BundlerTaskLoader;
  use GitTaskLoader;
  use RvmTaskLoader;
  use ScssLintTaskLoader;
  use ScssLintConfigFallbackTaskLoader;

  protected function getTaskLintScssExtension(string $workingDirectory): CollectionBuilder {
    $config = $this->getConfig();
    $gitHook = $config->get('command.marvin.settings.gitHook');
    $presetName = $this->getPresetNameByEnvironmentVariant();

    $marvinRootDir = Utils::marvinRootDir();
    $bundleGemFileName = 'Gemfile.scss-lint.rb';
    $relativeDirFromWdToMarvinEtcScssLint = Path::makeRelative("$marvinRootDir/etc/scss-lint", $workingDirectory);
    $relativeDirFromCwdToMarvinEtcScssLint = Path::makeRelative("$marvinRootDir/etc/scss-lint", getcwd());
    $bundleGemFilePath = "$relativeDirFromWdToMarvinEtcScssLint/$bundleGemFileName";

    $options = $this->getConfigValue("preset.$presetName");
    $options['workingDirectory'] = $workingDirectory;
    $options['envVarBundleGemFile'] = $bundleGemFilePath;
    $options += ['lintReporters' => []];
    $options['lintReporters'] += $this->getLintReporters();
    $options['failOn'] = 'warning';
    $options['failOnFileNotFound'] = FALSE;
    $options['failOnGlobDidNotMatch'] = FALSE;

    // @todo Check fallback files.
    $options['configFile'] = "$relativeDirFromWdToMarvinEtcScssLint/.scss-lint.yml";

    $taskBundlePlatformRubyVersion = $this
      ->taskBundlePlatformRubyVersion()
      ->setWorkingDirectory($relativeDirFromCwdToMarvinEtcScssLint)
      ->setBundleGemFile($bundleGemFileName)
      ->setAssetNamePrefix('ruby-version.');

    $rubyExecutable = $config->get('command.marvin.settings.rubyExecutable');
    if ($rubyExecutable) {
      $taskBundlePlatformRubyVersion->setRubyExecutable($rubyExecutable);
    }

    $bundleExecutable = $config->get('command.marvin.settings.bundleExecutable');
    if ($rubyExecutable) {
      $taskBundlePlatformRubyVersion->setBundleExecutable($bundleExecutable);
    }

    $taskRvmInfo = $this
      ->taskRvmInfo()
      ->deferTaskConfiguration('addRubyString', 'ruby-version.base');

    // @todo Create an official task.
    $taskEnvVarPathPrepare = function (RoboStateData $data): int {
      if (empty($data['rvm.info'])) {
        // @todo Error message.
        $this->yell('MISSING rvm.info');

        return 1;
      }

      $info = reset($data['rvm.info']);
      if (empty($info['homes']['ruby'])) {
        // @todo Error message.
        $this->yell('MISSING rvm.info.*.homes.ruby');

        return 2;
      }

      if (empty($info['homes']['gem'])) {
        // @todo Error message.
        $this->yell('MISSING rvm.info.*.homes.gem');

        return 3;
      }

      $data->offsetSet('scss-lint.envVarPath.ruby', $info['homes']['ruby'] . '/bin');
      $data->offsetSet('scss-lint.envVarPath.gem', $info['homes']['gem'] . '/bin');

      return 0;
    };

    if ($gitHook === 'pre-commit') {
      return $this
        ->collectionBuilder()
        ->addTask($this
          ->taskGitReadStagedFiles()
          ->setWorkingDirectory($workingDirectory)
          ->setCommandOnly(FALSE)
          ->setPaths(['*.scss'])
        )
        ->addTask($taskBundlePlatformRubyVersion)
        ->addTask($taskRvmInfo)
        ->addCode($taskEnvVarPathPrepare)
        ->addTask($this
          ->taskScssLintRunInput($options)
          ->deferTaskConfiguration('addEnvVarPath', 'scss-lint.envVarPath.ruby')
          ->deferTaskConfiguration('addEnvVarPath', 'scss-lint.envVarPath.gem')
          ->deferTaskConfiguration('setPaths', 'files')
        );
    }

    return $this
      ->collectionBuilder()
      ->addTask($taskBundlePlatformRubyVersion)
      ->addTask($taskRvmInfo)
      ->addCode($taskEnvVarPathPrepare)
      ->addTask($this
        ->taskScssLintRunFiles($options)
        ->setExclude(['*.css'])
        ->setPaths(['css/'])
        ->deferTaskConfiguration('addEnvVarPath', 'scss-lint.envVarPath.ruby')
        ->deferTaskConfiguration('addEnvVarPath', 'scss-lint.envVarPath.gem')
      );
  }

}

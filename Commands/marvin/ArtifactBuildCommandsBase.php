<?php

namespace Drush\Commands\marvin;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Robo\VersionNumberTaskLoader;
use Drupal\marvin\Utils as MarvinUtils;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;
use Sweetchuck\Robo\Git\GitTaskLoader;

abstract class ArtifactBuildCommandsBase extends ArtifactCommandsBase {

  use GitTaskLoader;
  use VersionNumberTaskLoader;

  /**
   * {@inheritdoc}
   */
  protected $customEventNamePrefix = 'marvin:artifact:build';

  /**
   * @var string
   *
   * @abstract
   */
  protected $artifactType = '';

  /**
   * @var string[]
   *
   * @todo This should be available from everywhere.
   */
  protected $versionPartNames = [
    'major',
    'minor',
    'patch',
    'pre-release',
    'meta-data',
  ];

  /**
   * @var string
   */
  protected $defaultVersionPartToBump = 'minor';

  /**
   * @var string
   */
  protected $srcDir = '.';

  /**
   * @var string
   */
  protected $artifactDir = '';

  /**
   * @var string
   */
  protected $versionPartToBump = '';

  /**
   * @var string
   */
  protected $versionTagNamePattern = '/^(v){0,1}(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)(|-(?P<special>[\da-zA-Z.]+))(|\+(?P<metadata>[\da-zA-Z.]+))$/';

  abstract protected function isApplicable(string $projectType): bool;

  protected function getBuildSteps(): array {
    return [
      'marvin.initStateData' => [
        'weight' => -240,
        'task' => $this->getTaskInitStateData(),
      ],
      'marvin.detectLatestVersionNumber' => [
        'weight' => -230,
        'task' => $this->getTaskDetectLatestVersionNumber(),
      ],
      'marvin.composeNextVersionNumber' => [
        'weight' => -220,
        'task' => $this->getTaskComposeNextVersionNumber(),
      ],
      'marvin.composeBuildDirPath' => [
        'weight' => -210,
        'task' => $this->getTaskComposeBuildDir(),
      ],
      'marvin.prepareDirectory' => [
        'weight' => -200,
        'task' => $this->getTaskPrepareDirectory(),
      ],
      'marvin.collectFiles' => [
        'weight' => -190,
        'task' => $this->getTaskCollectFiles(),
      ],
      'marvin.copyFiles' => [
        'weight' => -180,
        'task' => $this->getTaskCopyFiles(),
      ],
      'marvin.bumpVersionNumber.root' => [
        'weight' => 200,
        'task' => $this->getTaskBumpVersionNumberRoot(),
      ],
      'marvin.collectCustomExtensionDirs' => [
        'weight' => 210,
        'task' => $this->getTaskCollectChildExtensionDirs(),
      ],
      'marvin.bumpVersionNumber.extensions' => [
        'weight' => 220,
        'task' => $this->getTaskBumpVersionNumberExtensions('customExtensionDirs', 'nextVersionNumber.drupal'),
      ],
    ];
  }

  protected function getInitialStateData(): array {
    return [
      'coreVersion' => '8.x',
      'artifactType' => $this->artifactType,
      'versionPartToBump' => $this->versionPartToBump,
      'composerInfo' => ComposerInfo::create($this->srcDir),
    ];
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   *
   * @todo Create native Robo task.
   */
  protected function getTaskInitStateData() {
    return function (RoboStateData $data): int {
      $data->mergeData($this->getInitialStateData());

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   *
   * @todo Create native Robo task.
   */
  protected function getTaskDetectLatestVersionNumber() {
    return function (RoboStateData $data): int {
      $logger = $this->getLogger();
      $logContext = [
        'taskName' => 'DetectLatestVersionNumber',
      ];

      $logger->notice('{taskName}', $logContext);

      $result = $this
        ->taskGitTagList()
        ->setWorkingDirectory($this->srcDir)
        ->setMergedState(TRUE)
        ->run();

      if (!$result->wasSuccessful()) {
        return 0;
      }

      $tagNames = array_keys($result['gitTags'] ?? []);
      $tagNames = array_filter($tagNames, $this->getVersionTagNameFilter());
      usort($tagNames, $this->getVersionTagNameComparer());

      $tag = end($tagNames);
      if ($tag) {
        $data['latestVersionNumber.semver'] = $tag;
      }

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   *
   * @todo Create native Robo task.
   */
  protected function getTaskComposeNextVersionNumber() {
    return function (RoboStateData $data): int {
      $logger = $this->getLogger();
      $logContext = [
        'taskName' => 'ComposeNextVersionNumber',
      ];

      $logger->notice('{taskName}', $logContext);


      $data['nextVersionNumber.semver'] = NULL;
      $data['nextVersionNumber.drupal'] = NULL;

      $versionPartToBump = $data['versionPartToBump'] ?? $this->defaultVersionPartToBump;
      if (!in_array($versionPartToBump, $this->versionPartNames)) {
        $data['nextVersionNumber.semver'] = $versionPartToBump;
      }

      if (!$data['nextVersionNumber.semver']) {
        $data['nextVersionNumber.semver'] = (string) MarvinUtils::incrementSemVersion(
          $data['latestVersionNumber.semver'] ?? '0.0.0',
          $versionPartToBump
        );
      }

      if ($data['nextVersionNumber.semver']) {
        $data['nextVersionNumber.drupal'] = MarvinUtils::semverToDrupal(
          $data['coreVersion'],
          $data['nextVersionNumber.semver']
        );
      }

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   *
   * @todo Create native Robo task.
   */
  protected function getTaskComposeBuildDir() {
    return function (RoboStateData $data): int {
      $logger = $this->getLogger();
      $logContext = [
        'taskName' => 'ComposeBuildDir',
      ];

      $logger->notice('{taskName}', $logContext);

      $data['buildDir'] = "{$this->artifactDir}/{$data['nextVersionNumber.semver']}/{$data['artifactType']}";

      return 0;
    };
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskPrepareDirectory() {
    return $this
      ->taskMarvinPrepareDirectory()
      ->deferTaskConfiguration('setWorkingDirectory', 'buildDir');
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskCollectFiles() {
    return $this
      ->taskMarvinArtifactCollectFiles()
      ->setPackagePath($this->srcDir);
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskCopyFiles() {
    return $this
      ->taskMarvinCopyFiles()
      ->setSrcDir($this->srcDir)
      ->deferTaskConfiguration('setDstDir', 'buildDir')
      ->deferTaskConfiguration('setFiles', 'files');
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskBumpVersionNumberRoot() {
    return $this
      ->taskMarvinVersionNumberBumpExtensionInfo()
      ->setBumpExtensionInfo(FALSE)
      ->deferTaskConfiguration('setPackagePath', 'buildDir')
      ->deferTaskConfiguration('setVersionNumber', 'nextVersionNumber.drupal');
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  abstract protected function getTaskCollectChildExtensionDirs();

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function getTaskBumpVersionNumberExtensions(
    string $iterableStateKey,
    string $versionStateKey
  ) {
    $forEachTask = $this->taskForEach();

    $forEachTask
      ->deferTaskConfiguration('setIterable', $iterableStateKey)
      ->withBuilder(function (
        CollectionBuilder $builder,
        $key,
        string $extensionDir
      ) use (
        $forEachTask,
        $versionStateKey
      ) {
        if (!file_exists($extensionDir)) {
          return;
        }

        $builder->addTask(
          $this
            ->taskMarvinVersionNumberBumpExtensionInfo()
            ->setBumpComposerJson(FALSE)
            ->setPackagePath($extensionDir)
            ->setVersionNumber($forEachTask->getState()->offsetGet($versionStateKey))
        );
      });

    return $forEachTask;
  }

  protected function getVersionTagNameFilter(): callable {
    return function ($version): bool {
      return preg_match($this->versionTagNamePattern, (string) $version) === 1;
    };
  }

  protected function getVersionTagNameComparer(): callable {
    return 'version_compare';
  }

}

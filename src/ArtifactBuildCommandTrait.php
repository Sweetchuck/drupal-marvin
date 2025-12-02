<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Drupal\marvin\Artifact\BuildActionEvent;
use Drupal\marvin\Robo\ArtifactFilesTaskTrait;
use Drupal\marvin\Robo\FileSystemTaskTrait;
use Drupal\marvin\Robo\VersionNumberTaskTrait;
use Robo\Collection\CallableTask;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboStateData;
use Sweetchuck\Robo\Git\GitTaskLoader;

/**
 * @phpstan-ignore-next-line trait.unused
 */
trait ArtifactBuildCommandTrait {

  use ArtifactFilesTaskTrait;
  use FileSystemTaskTrait;
  use GitTaskLoader;
  use VersionNumberTaskTrait;

  /**
   * NOT an event handler.
   *
   * Call this from a real event handler.
   */
  protected function onEventMarvinArtifactBuildBase(
    BuildActionEvent $event,
    string $provider,
  ): static {
    $event->taskDefinitions += [
      "Initialize-StateDataBase.$provider" => [
        'weight' => -999,
        'description' => 'Initializes the state data.',
        'task' => $this->artifactBuildTaskInitStateData($event),
      ],
      "Prepare-BuildDir.$provider" => [
        'weight' => -950,
        'description' => 'Prepares the build directory.',
        'task' => $this->artifactBuildTaskPrepareDirectory($event),
      ],
      "Collect-FilesToCopy.$provider" => [
        'weight' => -800,
        'description' => 'Collects files to copy from srcDir to buildDir.',
        'task' => $this->artifactBuildTaskCollectFilesToCopy($event),
      ],
      "Copy-Files.$provider" => [
        'weight' => -750,
        'description' => 'Copies files from srcDir to buildDir.',
        'task' => $this->artifactBuildTaskCopyFiles($event),
      ],
      "Update-RootVersionNumber.$provider" => [
        'weight' => -700,
        'description' => 'Updates version number in the root composer.json.',
        'task' => $this->artifactBuildTaskUpdateRootVersionNumber($event),
      ],
      "Collect-SubExtensions.$provider" => [
        'weight' => -690,
        'description' => 'Collects all sub-extensions.',
        'task' => $this->artifactBuildTaskCollectExtensions($event),
      ],
      "Update-SubExtensionsVersionNumber.$provider" => [
        'weight' => -680,
        'description' => 'Updates version number in all sub-extensions.',
        'task' => $this->artifactBuildTaskUpdateExtensionsVersionNumber(
          $event,
          'subExtensions',
          'versionNumber',
        ),
      ],
      "Collect-FilesToDelete.$provider" => [
        'weight' => 850,
        'description' => 'Collects files to delete from the build directory.',
        'task' => $this->artifactBuildTaskCollectFilesToDelete($event),
      ],
      "Delete-Files.$provider" => [
        'weight' => 900,
        'description' => 'Deletes files from the build directory.',
        'task' => $this->artifactBuildTaskDeleteFiles($event),
      ],
    ];

    return $this;
  }

  /**
   * @phpstan-return marvin-artifact-vanilla-state
   */
  abstract protected function artifactBuildInitialStateData(BuildActionEvent $event): array;

  protected function artifactBuildTaskInitStateData(BuildActionEvent $event): TaskInterface {
    return new CallableTask(
      function (RoboStateData $data) use ($event): int {
        $data->mergeData($this->artifactBuildInitialStateData($event));

        return 0;
      },
      $event->collectionBuilder,
    );
  }

  protected function artifactBuildTaskPrepareDirectory(BuildActionEvent $event): TaskInterface {
    return $this
      ->taskMarvinPrepareDirectory()
      ->deferTaskConfiguration('setWorkingDirectory', 'buildDir');
  }

  protected function artifactBuildTaskCollectFilesToCopy(BuildActionEvent $event): TaskInterface {
    return $this
      ->taskMarvinArtifactCollectFilesToCopy()
      ->deferTaskConfiguration('setPackagePath', 'srcDir')
      ->deferTaskConfiguration('setBuildDir', 'buildDir')
      ->deferTaskConfiguration('setFilesToCopyDefinitions', 'filesToCopyDefinitions')
      ->deferTaskConfiguration('setComposerJsonFileName', 'composerJsonFileName');
  }

  protected function artifactBuildTaskCopyFiles(BuildActionEvent $event): TaskInterface {
    return $this
      ->taskMarvinArtifactCopyFiles()
      ->deferTaskConfiguration('setProjectRootDir', 'projectRootDir')
      ->deferTaskConfiguration('setSrcDir', 'srcDir')
      ->deferTaskConfiguration('setBuildDir', 'buildDir')
      ->deferTaskConfiguration('setFiles', 'filesToCopy');
  }

  protected function artifactBuildTaskUpdateRootVersionNumber(BuildActionEvent $event): TaskInterface {
    return $this
      ->taskMarvinUpdateExtensionVersionNumber()
      ->setUpdateExtensionInfo(FALSE)
      ->deferTaskConfiguration('setPackagePath', 'buildDir')
      ->deferTaskConfiguration('setVersionNumber', 'versionNumber');
  }

  /**
   * The preferred output state key is "subExtensions".
   *
   * @todo This could be implemented here, based on the project type.
   * - drupal-drush
   * - drupal-module
   * - drupal-theme
   * - drupal-profile
   * - drupal-recipe
   * - project or drupal-project.
   */
  abstract protected function artifactBuildTaskCollectExtensions(BuildActionEvent $event): TaskInterface;

  /**
   * @see \Robo\Collection\Tasks::taskForEach
   *
   * @code
   * use Robo\Collection\Tasks as LoopTaskLoader;
   * @endcode
   */
  abstract protected function taskForEach($collection = []);

  /**
   * @see \Robo\Task\Filesystem\Tasks::taskFilesystemStack
   *
   * @code
   * use Robo\Task\Filesystem\Tasks as FileSystemStackTaskLoader;
   * @endcode
   *
   * @todo Check if this method is still needed.
   */
  abstract protected function taskFilesystemStack();

  protected function artifactBuildTaskUpdateExtensionsVersionNumber(
    BuildActionEvent $event,
    string $iterableStateKey,
    string $versionStateKey,
  ): TaskInterface {
    $forEachTask = $this->taskForEach();

    $builder = function (
      CollectionBuilder $builder,
      $key,
      array $extension,
    ) use (
      $forEachTask,
      $versionStateKey
    ) {
      if (!file_exists($extension['dir'])) {
        // @todo Error handling. Why it is collected if it isn't exists?
        // @todo Has to be a directory or a symlink to a directory.
        return;
      }

      $versionNumber = $forEachTask->getState()->offsetGet($versionStateKey);
      $versionNumberString = (string) $versionNumber;
      $builder->addTask(
        $this
          ->taskMarvinUpdateExtensionVersionNumber()
          ->setUpdateComposerJson(FALSE)
          ->setPackagePath($extension['dir'])
          ->setVersionNumber($versionNumberString)
      );
    };

    $forEachTask
      ->deferTaskConfiguration('setIterable', $iterableStateKey)
      ->withBuilder($builder);

    return $forEachTask;
  }

  /**
   * @return \Closure|\Robo\Contract\TaskInterface
   */
  protected function artifactBuildTaskCollectFilesToDelete(BuildActionEvent $event) {
    return $this
      ->taskMarvinArtifactCollectFilesToDelete()
      ->deferTaskConfiguration('setBuildDir', 'buildDir')
      ->deferTaskConfiguration('setFilesToDeleteDefinitions', 'filesToDeleteDefinitions');
  }

  protected function artifactBuildTaskDeleteFiles(BuildActionEvent $event): TaskInterface {
    return $this
      ->taskMarvinArtifactDeleteFiles()
      ->deferTaskConfiguration('setProjectRootDir', 'projectRootDir')
      ->deferTaskConfiguration('setBuildDir', 'buildDir')
      ->deferTaskConfiguration('setFiles', 'filesToDelete');
  }

}

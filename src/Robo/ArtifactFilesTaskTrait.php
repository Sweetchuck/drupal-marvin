<?php

declare(strict_types=1);

namespace Drupal\marvin\Robo;

use Drupal\marvin\FinderBuilder;
use Drupal\marvin\Robo\Task\CollectArtifactFilesToCopyTask;
use Drupal\marvin\Robo\Task\CollectArtifactFilesToDeleteTask;
use Drupal\marvin\Robo\Task\CopyArtifactFilesTask;
use Drupal\marvin\Robo\Task\DeleteArtifactFilesTask;
use League\Container\ContainerAwareInterface;
use Symfony\Component\Filesystem\Filesystem;

trait ArtifactFilesTaskTrait {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\CollectArtifactFilesToCopyTask
   *
   * @phpstan-param marvin-robo-task-artifact-collect-files-to-copy-options $options
   */
  protected function taskMarvinArtifactCollectFilesToCopy(array $options = []) {
    $fs = NULL;
    $finderBuilder = NULL;
    if ($this instanceof ContainerAwareInterface) {
      $container = $this->getContainer();
      $fs = $container->get(Filesystem::class);
      $finderBuilder = $container->get(FinderBuilder::class);
    }

    /** @var \Drupal\marvin\Robo\Task\CollectArtifactFilesToCopyTask $task */
    $task = $this->task(
      CollectArtifactFilesToCopyTask::class,
      $fs,
      $finderBuilder,
    );

    $task->setOptions($options);

    return $task;
  }

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\CopyArtifactFilesTask
   *
   * @phpstan-param marvin-robo-task-copy-files-options $options
   */
  protected function taskMarvinArtifactCopyFiles(array $options = []) {
    $fs = NULL;
    if ($this instanceof ContainerAwareInterface) {
      $container = $this->getContainer();
      $fs = $container->get(Filesystem::class);
    }

    /** @var \Drupal\marvin\Robo\Task\CopyArtifactFilesTask $task */
    $task = $this->task(
      CopyArtifactFilesTask::class,
      $fs,
    );
    $task->setOptions($options);

    return $task;
  }

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\CollectArtifactFilesToDeleteTask
   *
   * @phpstan-param marvin-robo-task-artifact-collect-files-to-delete-options $options
   */
  protected function taskMarvinArtifactCollectFilesToDelete(array $options = []) {
    $fs = NULL;
    $finderBuilder = NULL;
    if ($this instanceof ContainerAwareInterface) {
      $container = $this->getContainer();
      $fs = $container->get(Filesystem::class);
      $finderBuilder = $container->get(FinderBuilder::class);
    }

    /** @var \Drupal\marvin\Robo\Task\CollectArtifactFilesToDeleteTask $task */
    $task = $this->task(
      CollectArtifactFilesToDeleteTask::class,
      $fs,
      $finderBuilder,
    );

    $task->setOptions($options);

    return $task;
  }

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin\Robo\Task\DeleteArtifactFilesTask
   *
   * @phpstan-param marvin-robo-task-delete-files-options $options
   */
  protected function taskMarvinArtifactDeleteFiles(array $options = []) {
    /** @var \Drupal\marvin\Robo\Task\DeleteArtifactFilesTask $task */
    $task = $this->task(DeleteArtifactFilesTask::class);
    $task->setOptions($options);

    return $task;
  }

}

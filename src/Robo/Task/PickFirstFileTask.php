<?php

declare(strict_types = 1);

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\Utils as MarvinUtils;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class PickFirstFileTask extends BaseTask implements StateAwareInterface {

  use StateAwareTrait;

  protected string $taskName = 'Marvin - Pick first file';

  protected Filesystem $fs;

  protected array $dirSuggestions = [];

  public function getDirSuggestions(): array {
    return $this->dirSuggestions;
  }

  public function setDirSuggestions(array $dirSuggestions): static {
    $this->dirSuggestions = gettype(reset($dirSuggestions)) === 'boolean' ?
      $dirSuggestions
      : array_fill_keys($dirSuggestions, TRUE);

    return $this;
  }

  public function addDirSuggestion(string $dir): static {
    $this->dirSuggestions[$dir] = TRUE;

    return $this;
  }

  public function removeDirSuggestion(string $dir): static {
    unset($this->dirSuggestions[$dir]);

    return $this;
  }

  /**
   * @var array
   */
  protected array $fileNameSuggestions = [];

  public function getFileNameSuggestions(): array {
    return $this->fileNameSuggestions;
  }

  public function setFileNameSuggestions(array $fileNameSuggestions): static {
    $this->fileNameSuggestions = gettype(reset($fileNameSuggestions)) === 'boolean' ?
      $fileNameSuggestions
      : array_fill_keys($fileNameSuggestions, TRUE);

    return $this;
  }

  public function addFileNameSuggestion(string $fileName): static {
    $this->fileNameSuggestions[$fileName] = TRUE;

    return $this;
  }

  public function removeFileNameSuggestion(string $fileName): static {
    unset($this->fileNameSuggestions[$fileName]);

    return $this;
  }

  protected string $assetNameBase = 'firstFile';

  public function getAssetNameBase(): string {
    return $this->assetNameBase;
  }

  public function setAssetNameBase(string $assetNameBase): static {
    $this->assetNameBase = $assetNameBase;

    return $this;
  }

  public function __construct(?Filesystem $fs = NULL) {
    $this->fs = $fs ?: new Filesystem();
  }

  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('dirSuggestions', $options)) {
      $this->setDirSuggestions($options['dirSuggestions']);
    }

    if (array_key_exists('fileNameSuggestions', $options)) {
      $this->setFileNameSuggestions($options['fileNameSuggestions']);
    }

    if (array_key_exists('assetNameBase', $options)) {
      $this->setAssetNameBase($options['assetNameBase']);
    }

    return $this;
  }

  protected function runAction(): static {
    $state = $this->getState();

    $assetNamePrefix = $this->getAssetNamePrefix();
    $assetNameBase = $this->getAssetNameBase();
    $assetName = $assetNamePrefix . $assetNameBase;
    if (!empty($state[$assetName])) {
      $this->printTaskDebug(
        'State data already has a "<info>{key}</info>" key with value "<info>{value}</info>"',
        [
          'key' => $assetName,
          'value' => $state[$assetName],
        ]
      );

      return $this;
    }

    $filter = new ArrayFilterEnabled();
    $dirs = array_filter($this->getDirSuggestions(), $filter);
    $files = array_filter($this->getFileNameSuggestions(), $filter);
    $first = MarvinUtils::pickFirstFile(
      array_keys($dirs),
      array_keys($files)
    );

    if ($first) {
      $this->assets[$assetNameBase] = Path::join($first['dir'], $first['file']);
      $this->assets["$assetNameBase.dir"] = $first['dir'];
    }

    return $this;
  }

}

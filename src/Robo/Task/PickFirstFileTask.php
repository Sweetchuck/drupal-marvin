<?php

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\Utils as MarvinUtils;
use Robo\State\StateAwareInterface;
use Robo\State\StateAwareTrait;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class PickFirstFileTask extends BaseTask implements StateAwareInterface {

  use StateAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Pick first file';

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * @var array
   */
  protected $dirSuggestions = [];

  public function getDirSuggestions(): array {
    return $this->dirSuggestions;
  }

  /**
   * @return $this
   */
  public function setDirSuggestions(array $dirSuggestions) {
    $this->dirSuggestions = gettype(reset($dirSuggestions)) === 'boolean' ?
      $dirSuggestions
      : array_fill_keys($dirSuggestions, TRUE);

    return $this;
  }

  /**
   * @return $this
   */
  public function addDirSuggestion(string $dir) {
    $this->dirSuggestions[$dir] = TRUE;

    return $this;
  }

  /**
   * @return $this
   */
  public function removeDirSuggestion(string $dir) {
    unset($this->dirSuggestions[$dir]);

    return $this;
  }

  /**
   * @var array
   */
  protected $fileNameSuggestions = [];

  public function getFileNameSuggestions(): array {
    return $this->fileNameSuggestions;
  }

  /**
   * @return $this
   */
  public function setFileNameSuggestions(array $fileNameSuggestions) {
    $this->fileNameSuggestions = gettype(reset($fileNameSuggestions)) === 'boolean' ?
      $fileNameSuggestions
      : array_fill_keys($fileNameSuggestions, TRUE);

    return $this;
  }

  /**
   * @return $this
   */
  public function addFileNameSuggestion(string $fileName) {
    $this->fileNameSuggestions[$fileName] = TRUE;

    return $this;
  }

  /**
   * @return $this
   */
  public function removeFileNameSuggestion(string $fileName) {
    unset($this->fileNameSuggestions[$fileName]);

    return $this;
  }

  /**
   * @var string
   */
  protected $assetNameBase = 'firstFile';

  public function getAssetNameBase(): string {
    return $this->assetNameBase;
  }

  /**
   * @return $this
   */
  public function setAssetNameBase(string $assetNameBase) {
    $this->assetNameBase = $assetNameBase;

    return $this;
  }

  public function __construct(?Filesystem $fs = NULL) {
    $this->fs = $fs ?: new Filesystem();
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
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

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    /** @var \Robo\State\Data $state */
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

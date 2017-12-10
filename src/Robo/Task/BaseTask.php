<?php

namespace Drush\marvin\Robo\Task;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask as RoboBaseTask;
use Robo\TaskAccessor;
use Robo\TaskInfo;
use Symfony\Component\Process\Process;

abstract class BaseTask extends RoboBaseTask implements
    ContainerAwareInterface,
    OutputAwareInterface {

  use ContainerAwareTrait;
  use IO;
  use TaskAccessor;

  /**
   * @var string
   */
  protected $taskName = '';

  /**
   * @var array
   */
  protected $assets = [];

  /**
   * @var array
   */
  protected $options = [];

  /**
   * @var string
   */
  protected $assetNamePrefix = '';

  public function getAssetNamePrefix(): string {
    return $this->assetNamePrefix;
  }

  /**
   * @return $this
   */
  public function setAssetNamePrefix(string $value) {
    $this->assetNamePrefix = $value;

    return $this;
  }

  /**
   * @var bool
   */
  protected $visibleStdOutput = FALSE;

  public function isStdOutputVisible(): bool {
    return $this->visibleStdOutput;
  }

  /**
   * @return $this
   */
  public function setVisibleStdOutput(bool $visible) {
    $this->visibleStdOutput = $visible;

    return $this;
  }

  /**
   * @var int
   */
  protected $actionExitCode = 0;

  /**
   * @var string
   */
  protected $actionStdOutput = '';

  /**
   * @var string
   */
  protected $actionStdError = '';

  public function getTaskName(): string {
    return $this->taskName ?: TaskInfo::formatTaskName($this);
  }

  /**
   * @return $this
   */
  protected function initOptions() {
    $this->options = [
      'assetNamePrefix' => [
        'type' => 'other',
        'value' => $this->getAssetNamePrefix(),
      ],
    ];

    return $this;
  }

  /**
   * @return $this
   */
  public function setOptions(array $options) {
    if (array_key_exists('assetNamePrefix', $options)) {
      $this->setAssetNamePrefix($options['assetNamePrefix']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function run(): Result {
    return $this
      ->runPrepare()
      ->runHeader()
      ->runValidate()
      ->runAction()
      ->runProcessOutputs()
      ->runReturn();
  }

  /**
   * @return $this
   */
  protected function runPrepare() {
    $this->initOptions();

    return $this;
  }

  /**
   * @return $this
   */
  protected function runHeader() {
    $this->printTaskInfo('');

    return $this;
  }

  /**
   * @return $this
   */
  protected function runValidate() {
    return $this;
  }

  /**
   * @return $this
   */
  abstract protected function runAction();

  /**
   * @return $this
   */
  protected function runProcessOutputs() {
    return $this;
  }

  protected function runReturn(): Result {
    return new Result(
      $this,
      $this->actionExitCode,
      $this->actionStdError,
      $this->getAssetsWithPrefixedNames()
    );
  }

  protected function getAssetsWithPrefixedNames(): array {
    $prefix = $this->getAssetNamePrefix();
    if (!$prefix) {
      return $this->assets;
    }

    $assets = [];
    foreach ($this->assets as $key => $value) {
      $assets["{$prefix}{$key}"] = $value;
    }

    return $assets;
  }

  protected function runCallback(string $type, string $data): void {
    switch ($type) {
      case Process::OUT:
        if ($this->isStdOutputVisible()) {
          $this->output()->write($data);
        }
        break;

      case Process::ERR:
        $this->printTaskError($data);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getTaskContext($context = NULL) {
    $context = parent::getTaskContext($context);
    $context['name'] = $this->getTaskName();

    return $context;
  }

}

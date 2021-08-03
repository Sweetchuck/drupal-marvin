<?php

declare(strict_types = 1);

namespace Drupal\marvin\Robo\Task;

use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboStateData;
use Sweetchuck\Robo\Stringy\StringyTaskLoader;
use Sweetchuck\Utils\ArrayFilterInterface;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

/**
 * @todo This task shouldn't deal with a file, just with the commit message string directly.
 */
class GitCommitMsgValidatorTask extends BaseTask implements BuilderAwareInterface {

  use StringyTaskLoader;

  protected string $taskName = 'Marvin - Git commit message validator';

  protected string $fileName = '';

  public function getFileName(): string {
    return $this->fileName;
  }

  /**
   * @return $this
   */
  public function setFileName(string $fileName) {
    $this->fileName = $fileName;

    return $this;
  }

  protected array $rules = [];

  public function getRules(): array {
    return $this->rules;
  }

  /**
   * @return $this
   */
  public function setRules(array $rules) {
    $this->rules = $rules;

    return $this;
  }

  /**
   * @return $this
   */
  public function addRule(array $rule) {
    $this->rules[$rule['name']] = $rule;

    return $this;
  }

  /**
   * @return $this
   */
  public function removeRule($rule) {
    $ruleName = is_array($rule) ? $rule['name'] : (string) $rule;
    unset($this->rules[$ruleName]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('fileName', $options)) {
      $this->setFileName($options['fileName']);
    }

    if (array_key_exists('rules', $options)) {
      $this->setRules($options['rules']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $result = $this
      ->collectionBuilder()
      ->addCode($this->getTaskRead($this->getFileName()))
      ->addTask($this->getTaskSanitize())
      ->addCode($this->getTaskValidate())
      ->run();

    $this->actionExitCode = $result->getExitCode();
    $this->actionStdOutput = $result->getOutputData() ?? '';
    $this->actionStdError = $result->getMessage();

    return $this;
  }

  /**
   * @return callable|\Robo\Contract\TaskInterface
   */
  protected function getTaskRead(string $commitMsgFileName) {
    return function (RoboStateData $data) use ($commitMsgFileName): int {
      $content = @file_get_contents($commitMsgFileName);
      if ($content === FALSE) {
        throw new \RuntimeException(
          sprintf('Read file content from "%s" file failed', $commitMsgFileName),
          1
        );
      }

      $data['commitMsg'] = $content;

      return 0;
    };
  }

  protected function getTaskSanitize(): TaskInterface {
    return $this
      ->taskStringy()
      ->callRegexReplace('(^|(\r\n)|(\n\r)|\r|\n)#([^\r\n]*)|$', '')
      ->callTrim("\n\r")
      ->setAssetNamePrefix('commitMsg.')
      ->deferTaskConfiguration('setString', 'commitMsg');
  }

  protected function getTaskValidate(): \Closure {
    return function (RoboStateData $data): int {
      $exitCode = 0;
      foreach ($this->getPreparedRules() as $rule) {
        // @todo Pattern validation.
        if (preg_match($rule['pattern'], $data['commitMsg.stringy']) !== 1) {
          $logEntry = $this->getRuleErrorLogEntry($rule);
          $this->logger->error($logEntry['message'], $logEntry['context']);
          $exitCode = 1;
        }
      }

      if ($exitCode) {
        $this->logger->error(
          "The actual commit message is:\nBEGIN\n<info>{commitMessage}</info>\nEND",
          [
            'commitMessage' => $data['commitMsg.stringy'],
          ]
        );
      }

      return $exitCode;
    };
  }

  protected function getPreparedRules(): array {
    $rules = $this->getRules();
    foreach (array_keys($rules) as $ruleName) {
      $this->applyDefaultsToRule($ruleName, $rules[$ruleName]);
    }

    return array_filter($rules, $this->getRuleFilter());
  }

  protected function applyDefaultsToRule(string $ruleName, array &$rule) {
    $rule['name'] = $ruleName;
    $rule += [
      'enabled' => TRUE,
      'description' => '- Missing -',
      'examples' => [],
    ];

    return $this;
  }

  protected function getRuleFilter(): ArrayFilterInterface {
    return new ArrayFilterEnabled();
  }

  protected function getRuleErrorLogEntry(array $rule): array {
    $entry = [
      'context' => [
        'ruleName' => $rule['name'],
      ],
      'message' => [
        'Commit message validation with rule <info>{ruleName}</info> failed.',
        $rule['description'],
      ],
    ];

    $examples = array_filter($rule['examples'], new ArrayFilterEnabled());
    if ($examples) {
      $entry['message'][] = 'Valid commit message examples are:';
      $entry['message'] = array_merge($entry['message'], array_keys($examples, TRUE));
    }

    $entry['message'] = implode(PHP_EOL, $entry['message']);

    return $entry;
  }

}

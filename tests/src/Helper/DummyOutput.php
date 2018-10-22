<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Helper;

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * @method $this getErrorOutput()
 */
class DummyOutput extends ConsoleOutput {

  /**
   * @var int
   */
  protected static $instanceCounter = 0;

  /**
   * @var string
   */
  public $output = '';

  /**
   * @var int
   */
  public $instanceId = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct($config) {
    parent::__construct($config);
    $this->instanceId = static::$instanceCounter++;

    $errorOutput = $this;
    if (empty($config['stdErr'])) {
      $config['stdErr'] = TRUE;
      $errorOutput = new static($config);
    }

    $this->setErrorOutput($errorOutput);
  }

  /**
   * {@inheritdoc}
   */
  protected function doWrite($message, $newline) {
    $this->output .= $message . ($newline ? PHP_EOL : '');
  }

}

<?php

namespace Drush\marvin;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property-read string $packageVendor
 * @property-read string $packageName
 */
class ComposerInfo implements \ArrayAccess {

  protected static $instances = [];

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * @var array
   */
  protected $json = [];

  /**
   * @var string
   */
  protected $jsonFileName = '';

  /**
   * @var int
   */
  protected $jsonChangedTime = 0;

  /**
   * @var array
   */
  protected $lock = [];

  /**
   * @var string
   */
  protected $lockFileName = '';

  /**
   * @var int
   */
  protected $lockChangedTime = 0;

  /**
   * @var array
   */
  protected $jsonDefault = [
    'type' => 'library',
    'config' => [
      'bin-dir' => 'vendor/bin',
      'vendor-dir' => 'vendor',
    ],
  ];

  /**
   * @return static
   */
  public static function create(string $jsonFileName = '', ?Filesystem $fs = NULL) {
    if (!$jsonFileName) {
      $jsonFileName = getenv('COMPOSER') ?: 'composer.json';
    }

    if (!isset(static::$instances[$jsonFileName])) {
      static::$instances[$jsonFileName] = new static($jsonFileName, $fs);
    }

    return static::$instances[$jsonFileName];
  }

  protected function __construct(string $jsonFileName, ?Filesystem $fs = NULL) {
    $this->fs = $fs ?: new Filesystem();
    $this->jsonFileName = $jsonFileName;
    $this->initLockFileName();
  }

  public function __destruct() {
    unset(static::$instances[$this->jsonFileName]);
  }

  /**
   * @return $this
   */
  protected function initLockFileName() {
    $jsonExtension = pathinfo($this->jsonFileName, PATHINFO_EXTENSION);
    $this->lockFileName = basename($this->jsonFileName, $jsonExtension) . 'lock';

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return array_key_exists($offset, $this->getJson());
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($offset) {
    $this->initJson();

    return $this->json[$offset];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    $this->initJson();
    $this->json[$offset] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    $this->initJson();
    unset($this->json[$offset]);
  }

  public function __get($name) {
    $this->initJson();

    if (array_key_exists($name, $this->json)) {
      return $this->json[$name];
    }

    switch ($name) {
      case 'packageVendor':
      case 'packageName':
        if (!isset($this->json['name'])) {
          return NULL;
        }

        list($packageVendor, $packageName) = explode('/', $this->json['name']) + [1 => ''];

        return $name === 'packageVendor' ? $packageVendor : $packageName;

    }

    $pattern = 'Undefined property via __get(): "%s" in "%s" on line "%d"';
    $trace = debug_backtrace();
    trigger_error(
      sprintf($pattern, $name, $trace[0]['file'], $trace[0]['line']),
      E_USER_NOTICE
    );

    return NULL;
  }

  /**
   * @return $this
   */
  protected function init() {
    $this
      ->initJson()
      ->initLock();

    return $this;
  }

  protected function initJson() {
    $this->checkJsonExists();
    $changedTime = filectime($this->jsonFileName);
    if ($changedTime > $this->jsonChangedTime) {
      $this->json = array_replace_recursive(
        $this->jsonDefault,
        json_decode(file_get_contents($this->jsonFileName), TRUE)
      );

      $this->jsonChangedTime = $changedTime;
    }

    return $this;
  }

  protected function initLock() {
    if ($this->fs->exists($this->lockFileName)) {
      $changedTime = filectime($this->lockFileName);
      if ($changedTime > $this->lockChangedTime) {
        $this->lock = json_decode(file_get_contents($this->lockFileName), TRUE);
        $this->lockChangedTime = $changedTime;
      }
    }
    else {
      $this->lock = [];
    }

    return $this;
  }

  /**
   * @return $this
   */
  public function invalidate() {
    $this->jsonChangedTime = 0;
    $this->lockChangedTime = 0;

    return $this;
  }

  public function getJson(): array {
    return $this
      ->initJson()
      ->json;
  }

  public function getLock(): array {
    return $this
      ->initLock()
      ->lock;
  }

  /**
   * @return $this
   */
  protected function checkJsonExists() {
    if (!$this->fs->exists($this->jsonFileName)) {
      throw new FileNotFoundException(NULL, 1, NULL, $this->jsonFileName);
    }

    return $this;
  }

}

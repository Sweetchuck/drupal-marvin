<?php

namespace Drush\marvin;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

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

  public function getJsonFileName(): string {
    return $this->jsonFileName;
  }

  public function getWorkingDirectory(): string {
    return Path::getDirectory($this->getJsonFileName());
  }

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

  public function getLockFileName(): string {
    return $this->lockFileName;
  }

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
   * @return $this
   */
  public static function create(string $jsonFileName = '', ?Filesystem $fs = NULL, string $baseDir = '') {
    if (!$jsonFileName) {
      $jsonFileName = getenv('COMPOSER') ?: 'composer.json';
    }

    if (!isset(static::$instances[$jsonFileName])) {
      static::$instances[$jsonFileName] = new static($jsonFileName, $fs, $baseDir);
    }

    return static::$instances[$jsonFileName];
  }

  protected function __construct(string $jsonFileName, ?Filesystem $fs = NULL, string $baseDir = '') {
    $this->fs = $fs ?: new Filesystem();
    $this->jsonFileName = Path::isAbsolute($jsonFileName) ? $jsonFileName : Path::join(($baseDir ?: getcwd()), $jsonFileName);
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
    $jsonExtensionLength = mb_strlen($jsonExtension);
    $this->lockFileName = mb_substr($this->jsonFileName, 0, $jsonExtensionLength * -1) . 'lock';

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

  /**
   * @return $this
   */
  protected function initLock() {
    return $this
      ->initLockReadFile()
      ->initLockChangeKeys();
  }

  /**
   * @return $this
   */
  protected function initLockReadFile() {
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
  protected function initLockChangeKeys() {
    foreach (['packages', 'packages-dev'] as $mainKey) {
      if (!isset($this->lock[$mainKey])) {
        continue;
      }

      foreach ($this->lock[$mainKey] as $key => $package) {
        unset($this->lock[$mainKey][$key]);
        $this->lock[$mainKey][$package['name']] = $package;
      }
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

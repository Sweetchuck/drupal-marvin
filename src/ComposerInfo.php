<?php

declare(strict_types = 1);

namespace Drupal\marvin;

use Stringy\StaticStringy;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @property-read string $name
 * @property-read string $packageVendor
 * @property-read string $packageName
 *
 * @todo A "write" or a "dump" method would be handy.
 */
class ComposerInfo implements \ArrayAccess {

  /**
   * @var static[]
   */
  protected static array $instances = [];

  protected Filesystem $fs;

  protected array $json = [];

  protected string $jsonFileName = '';

  public function getJsonFileName(): string {
    return $this->jsonFileName;
  }

  public function getWorkingDirectory(): string {
    return Path::getDirectory($this->getJsonFileName());
  }

  protected int $jsonChangedTime = 0;

  protected array $lock = [];

  protected string $lockFileName = '';

  public function getLockFileName(): string {
    return $this->lockFileName;
  }

  protected int $lockChangedTime = 0;

  protected array $jsonDefault = [
    'type' => 'library',
    'config' => [
      'bin-dir' => 'vendor/bin',
      'vendor-dir' => 'vendor',
    ],
  ];

  public static function create(string $baseDir = '', string $jsonFileName = '', ?Filesystem $fs = NULL): static {
    if (!$jsonFileName) {
      $jsonFileName = Utils::getComposerJsonFileName();
    }

    $instanceId = Path::isAbsolute($jsonFileName) ? $jsonFileName : Path::join(($baseDir ?: getcwd()), $jsonFileName);
    if (!isset(static::$instances[$instanceId])) {
      static::$instances[$instanceId] = new static($jsonFileName, $fs, $baseDir);
    }

    return static::$instances[$instanceId];
  }

  protected function __construct(string $jsonFileName, ?Filesystem $fs = NULL, string $baseDir = '') {
    $this->fs = $fs ?: new Filesystem();
    $this->jsonFileName = Path::isAbsolute($jsonFileName) ? $jsonFileName : Path::join(($baseDir ?: getcwd()), $jsonFileName);
    $this->initLockFileName();
  }

  public function __destruct() {
    unset(static::$instances[$this->jsonFileName]);
  }

  protected function initLockFileName(): static {
    $jsonExtension = pathinfo($this->jsonFileName, PATHINFO_EXTENSION);
    $jsonExtensionLength = mb_strlen($jsonExtension);
    $this->lockFileName = mb_substr($this->jsonFileName, 0, $jsonExtensionLength * -1) . 'lock';

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset): bool {
    return array_key_exists($offset, $this->getJson());
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function offsetGet($offset) {
    $this->initJson();

    return $this->json[$offset];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value): void {
    $this->initJson();
    $this->json[$offset] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset): void {
    $this->initJson();
    unset($this->json[$offset]);
  }

  public function __get($name) {
    $this->initJson();

    if (array_key_exists($name, $this->json)) {
      return $this->json[$name];
    }

    switch ($name) {
      case 'name':
        return $this->json['name'] ?? NULL;

      case 'packageVendor':
      case 'packageName':
        if (!isset($this->json['name'])) {
          return NULL;
        }

        [$packageVendor, $packageName] = explode('/', $this->json['name']) + [1 => ''];

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

  protected function initJson(): static {
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

  protected function initLock(): static {
    return $this
      ->initLockReadFile()
      ->initLockChangeKeys();
  }

  protected function initLockReadFile(): static {
    if (!$this->fs->exists($this->lockFileName)) {
      $this->lock = [];

      return $this;
    }

    $changedTime = filectime($this->lockFileName);
    if ($changedTime > $this->lockChangedTime) {
      $this->lock = json_decode(file_get_contents($this->lockFileName), TRUE);
      $this->lockChangedTime = $changedTime;
    }

    return $this;
  }

  protected function initLockChangeKeys(): static {
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

  public function invalidate(): static {
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

  public function getDrupalExtensionInstallDir(string $type): ?string {
    $type = StaticStringy::ensureLeft($type, 'drupal-');
    $json = $this->getJson();
    $installerPaths = $json['extra']['installer-paths'] ?? [];

    foreach ($installerPaths as $dir => $conditions) {
      if (in_array("type:$type", $conditions)) {
        return $dir;
      }
    }

    return NULL;
  }

  public function getDrupalRootDir(): string {
    $installerPaths = $this['extra']['installer-paths'] ?? [];
    foreach ($installerPaths as $installDir => $rules) {
      if (in_array('drupal/core', $rules) || in_array('type:drupal-core', $rules)) {
        $installDir = strtr(
          $installDir,
          [
            '{$name}' => 'core',
          ]
        );

        return dirname($installDir);
      }
    }

    return $this['config']['vendor-dir'] . '/drupal';
  }

  protected function checkJsonExists(): static {
    if (!$this->fs->exists($this->jsonFileName)) {
      throw new FileNotFoundException(NULL, 1, NULL, $this->jsonFileName);
    }

    return $this;
  }

}

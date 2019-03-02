<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drupal\marvin\ComposerInfo;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Webmozart\PathUtil\Path;

class CommandsTestBase extends TestCase {

  protected static function getMarvinRootDir(): string {
    return Path::canonicalize(__DIR__ . '/../../../..');
  }

  /**
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $vfs;

  /**
   * @var \Drupal\marvin\ComposerInfo
   */
  protected $composerInfo;

  /**
   * @var string
   */
  protected $marvinRootDir = '';

  protected function setUp() {
    parent::setUp();

    $this
      ->setUpVfs()
      ->setUpComposerInfo();
  }

  protected function setUpVfs() {
    $this->vfs = vfsStream::setup(
      __FUNCTION__,
      NULL,
      [
        'project_01' => [
          'docroot' => [],
          'vendor' => [],
          'composer.json' => '{"name": "drupal/marvin-tester"}',
        ],
      ]
    );

    return $this;
  }

  protected function setUpComposerInfo() {
    $this->composerInfo = ComposerInfo::create($this->vfs->url() . '/project_01');

    return $this;
  }

  protected function getDefaultConfigData(): array {
    return [
      'drush' => [
        'vendor-dir' => $this->vfs->url() . '/vendor',
      ],
    ];
  }

}

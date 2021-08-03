<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drupal\marvin\ComposerInfo;
use Drush\Commands\marvin\CommandsBase;
use League\Container\Container;
use League\Container\ContainerInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Sweetchuck\LintReport\Reporter\BaseReporter as LintBaseReporter;
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

  /**
   * @var \Drush\Commands\marvin\CommandsBase
   */
  protected $commands;

  /**
   * @var string
   */
  protected $commandsClass = CommandsBase::class;

  protected function setUp(): void {
    parent::setUp();

    $this
      ->setUpVfs()
      ->setUpComposerInfo()
      ->setUpCommands();
  }

  /**
   * @return $this
   */
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

  /**
   * @return $this
   */
  protected function setUpComposerInfo() {
    $this->composerInfo = ComposerInfo::create($this->vfs->url() . '/project_01');

    return $this;
  }

  /**
   * @return $this
   */
  protected function setUpCommands() {
    $this->commands = new $this->commandsClass($this->composerInfo);

    return $this;
  }

  protected function getDefaultConfigData(): array {
    return [
      'drush' => [
        'vendor-dir' => $this->vfs->url() . '/vendor',
      ],
    ];
  }

  protected function initContainerLintReporters(ContainerInterface $container) {
    $lintServices = LintBaseReporter::getServices();
    foreach ($lintServices as $name => $class) {
      if ($container->has($name)) {
        continue;
      }

      if ($container instanceof Container) {
        $container->share($name, $class);
      }
    }
  }

}

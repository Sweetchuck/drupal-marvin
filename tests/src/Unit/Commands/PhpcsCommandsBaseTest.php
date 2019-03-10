<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drush\Commands\marvin\PhpcsCommandsBase;
use org\bovigo\vfs\vfsStream;
use ReflectionClass;
use Robo\Config\Config;
use Symfony\Component\Yaml\Yaml;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\PhpcsCommandsBase<extended>
 */
class PhpcsCommandsBaseTest extends CommandsTestBase {

  public function testGetClassKey(): void {
    $commands = new PhpcsCommandsBase($this->composerInfo);

    $methodName = 'getClassKey';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin.phpcs.a', $method->invokeArgs($commands, ['a']));
  }

  public function testGetConfigValue(): void {
    $configData = [
      'marvin' => [
        'phpcs' => [
          'my_key' => 'my_value',
        ],
      ],
    ];

    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $commands = new PhpcsCommandsBase($this->composerInfo);
    $commands->setConfig($config);

    $methodName = 'getConfigValue';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('my_value', $method->invokeArgs($commands, ['my_key']));
  }

  public function testGetCustomEventNamePrefix(): void {
    $commands = new PhpcsCommandsBase($this->composerInfo);
    $methodName = 'getCustomEventNamePrefix';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin:phpcs', $method->invokeArgs($commands, []));
  }

  public static function casesGetPresetNameByEnvironmentVariant(): array {
    $default = Yaml::parseFile(static::getMarvinRootDir() . '/Commands/drush.yml');

    return [
      'empty' => [
        'default',
        [],
      ],
      'default' => [
        'default',
        $default,
      ],
      'basic' => [
        'd',
        [
          'marvin' => [
            'environment' => 'ci',
            'ci' => 'jenkins',
            'phpcs' => [
              'defaultPreset' => [
                'dev' => 'a',
                'devPreCommit' => 'b',
                'ci' => 'c',
                'ciJenkins' => 'd',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetPresetNameByEnvironmentVariant
   */
  public function testGetPresetNameByEnvironmentVariant(string $expected, array $configData): void {
    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $commands = new PhpcsCommandsBase($this->composerInfo);
    $commands->setConfig($config);

    $methodName = 'getPresetNameByEnvironmentVariant';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame($expected, $method->invokeArgs($commands, []));
  }

  public static function casesGetPhpcsConfigurationFileName(): array {
    return [
      'empty' => [
        '',
        [],
        '.',
      ],
      'only phpcs.xml.dist' => [
        'phpcs.xml.dist',
        [
          'phpcs.xml.dist' => '',
        ],
        '.',
      ],
      'only phpcs.xml' => [
        'phpcs.xml',
        [
          'phpcs.xml' => '',
        ],
        '.',
      ],
      'both' => [
        'phpcs.xml',
        [
          'phpcs.xml.dist' => '',
          'phpcs.xml' => '',
        ],
        '.',
      ],
    ];
  }

  /**
   * @dataProvider casesGetPhpcsConfigurationFileName
   */
  public function testGetPhpcsConfigurationFileName(string $expected, array $vfsStructure, string $directory): void {
    $baseDir = __FUNCTION__;
    $vfsBase = vfsStream::create([$baseDir => $vfsStructure], $this->vfs);
    $this->vfs->addChild($vfsBase);

    $commands = new PhpcsCommandsBase($this->composerInfo);

    $methodName = 'getPhpcsConfigurationFileName';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    if ($expected) {
      $expected = $this->vfs->url() . "/$baseDir/$expected";
    }

    $directory = $this->vfs->url() . "/$baseDir/$directory";

    static::assertSame($expected, $method->invokeArgs($commands, [$directory]));
  }

}

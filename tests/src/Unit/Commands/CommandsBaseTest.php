<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drush\Commands\marvin\CommandsBase;
use League\Container\Container;
use ReflectionClass;
use Robo\Config\Config;

/**
 * @covers \Drush\Commands\marvin\CommandsBase<extended>
 */
class CommandsBaseTest extends CommandsTestBase {

  /**
   * @dataProvider casesGetEnvironment
   */
  public static function casesGetEnvironment(): array {
    return [
      'basic' => [
        'dev',
      ],
      'override' => [
        'prod',
        [
          'marvin' => ['environment' => 'prod'],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetEnvironment
   */
  public function testGetEnvironment(string $expected, array $configData = []) {
    $configData = array_replace_recursive($this->getDefaultConfigData(), $configData);
    $config = new Config($configData);
    $container = new Container();

    $commands = new CommandsBase($this->composerInfo);
    $commands->setConfig($config);
    $commands->setContainer($container);

    $methodName = 'getEnvironment';
    $class = new ReflectionClass($commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    /** @var string $environment */
    $environment = $method->invokeArgs($commands, []);

    static::assertSame($expected, $environment);
  }

  public static function casesGetEnvironmentVariants(): array {
    return [
      'basic' => [
        ['dev', 'default'],
      ],
      'override' => [
        ['prod', 'default'],
        [
          'marvin' => ['environment' => 'prod'],
        ],
      ],
      'override with gitHook' => [
        ['devPreCommit', 'dev', 'default'],
        [
          'marvin' => ['environment' => 'dev', 'gitHook' => 'pre-commit'],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetEnvironmentVariants
   */
  public function testGetEnvironmentVariants(array $expected, array $configData = []) {
    $configData = array_replace_recursive($this->getDefaultConfigData(), $configData);
    $container = new Container();

    $this->commands->setConfig(new Config($configData));
    $this->commands->setContainer($container);

    $methodName = 'getEnvironmentVariants';
    $class = new ReflectionClass($this->commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    /** @var string[] $environment */
    $envVariants = $method->invokeArgs($this->commands, []);

    static::assertSame($expected, $envVariants);
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Commands;

use Drush\Commands\marvin\Test\PhpunitCommandsBase;
use ReflectionClass;
use Robo\Config\Config;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Drush\Commands\marvin\Test\PhpunitCommandsBase<extended>
 */
class PhpunitCommandsBaseTest extends CommandsTestBase {

  /**
   * @var \Drush\Commands\marvin\Test\PhpunitCommandsBase
   */
  protected $commands;

  /**
   * @var string
   */
  protected $commandsClass = PhpunitCommandsBase::class;

  public function testGetClassKey(): void {
    $methodName = 'getClassKey';
    $class = new ReflectionClass($this->commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin.phpunit.a', $method->invokeArgs($this->commands, ['a']));
  }

  public function testGetConfigValue(): void {
    $configData = [
      'marvin' => [
        'phpunit' => [
          'my_key' => 'my_value',
        ],
      ],
    ];

    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );
    $config = new Config($configData);

    $this->commands->setConfig($config);

    $methodName = 'getConfigValue';
    $class = new ReflectionClass($this->commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('my_value', $method->invokeArgs($this->commands, ['my_key']));
  }

  public function testGetCustomEventNamePrefix(): void {
    $methodName = 'getCustomEventNamePrefix';
    $class = new ReflectionClass($this->commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame('marvin:test:phpunit', $method->invokeArgs($this->commands, []));
  }

  public static function casesGetTestSuiteNamesByEnvironmentVariant(): array {
    $default = Yaml::parseFile(static::getMarvinRootDir() . '/Commands/drush.yml');

    return [
      'empty' => [
        [],
        [],
      ],
      'default' => [
        [
          'Unit',
        ],
        array_replace_recursive(
          $default,
          [
            'marvin' => [
              'gitHook' => 'pre-commit',
            ],
          ]
        ),
      ],
      'basic' => [
        [
          'b',
        ],
        [
          'marvin' => [
            'environment' => 'dev',
            'ci' => 'jenkins',
            'gitHook' => 'pre-commit',
            'phpunit' => [
              'testSuite' => [
                'dev' => [
                  'a' => TRUE,
                ],
                'devPreCommit' => [
                  'b' => TRUE,
                  'c' => FALSE,
                ],
                'ci' => [],
                'ciJenkins' => [],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGetTestSuiteNamesByEnvironmentVariant
   */
  public function testGetTestSuiteNamesByEnvironmentVariant(?array $expected, array $configData): void {
    $configData = array_replace_recursive(
      $this->getDefaultConfigData(),
      $configData
    );

    $this->commands->setConfig(new Config($configData));

    $methodName = 'getTestSuiteNamesByEnvironmentVariant';
    $class = new ReflectionClass($this->commands);
    $method = $class->getMethod($methodName);
    $method->setAccessible(TRUE);

    static::assertSame($expected, $method->invokeArgs($this->commands, []));
  }

}

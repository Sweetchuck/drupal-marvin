<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Robo\State\Data as RoboStateData;

/**
 * @covers \Drupal\marvin\Robo\Task\PhpcsConfigFallbackTask
 * @covers \Drupal\marvin\Robo\PhpcsConfigFallbackTaskLoader
 */
class PhpcsConfigFallbackTaskTest extends TaskTestBase {

  public function casesRunSuccessCollect(): array {
    return [
      'type.library' => [
        [
          'exitCode' => 0,
          'assets' => [
            'files' => [],
            'exclude-patterns' => [],
          ],
        ],
        [
          'composer.json' => json_encode([
            'name' => 'a/b',
            'type' => 'library',
          ]),
        ],
      ],
      'type.drupal-project' => [
        [
          'exitCode' => 0,
          'assets' => [
            'files' => [
              'drush/custom/' => TRUE,
              'docroot/modules/custom/' => TRUE,
              'docroot/profiles/custom/' => FALSE,
              'docroot/themes/custom/' => TRUE,
              'tests/behat/subcontexts/' => TRUE,
            ],
            'exclude-patterns' => [],
          ],
        ],
        [
          'composer.json' => json_encode([
            'name' => 'drupal/project_01',
            'type' => 'drupal-project',
            'extra' => [
              'installer-paths' => [
                'docroot/core' => ['type:drupal-core'],
              ],
            ],
          ]),
          'drush' => [
            'custom' => [
              'foo' => [],
            ],
          ],
          'docroot' => [
            'modules' => [
              'custom' => [],
            ],
            'themes' => [
              'custom' => [],
            ],
          ],
          'tests' => [
            'behat' => [
              'subcontexts' => [],
            ],
          ],
        ],
      ],
      'type.drupal-module' => [
        [
          'exitCode' => 0,
          'assets' => [
            'files' => [
              'Commands/' => FALSE,
              'src/' => TRUE,
              'tests/' => FALSE,
              'dummy_m1.module' => TRUE,
            ],
            'exclude-patterns' => [],
          ],
        ],
        [
          'src' => [],
          'composer.json' => json_encode([
            'name' => 'drupal/dummy_m1',
            'type' => 'drupal-module',
          ]),
          'dummy_m1.module' => '<?php',
        ],
      ],
      'type.drupal-profile' => [
        [
          'exitCode' => 0,
          'assets' => [
            'files' => [
              'Commands/' => FALSE,
              'src/' => TRUE,
              'tests/' => FALSE,
              'modules/custom/' => FALSE,
              'themes/custom/' => FALSE,
              'dummy_m1.profile' => TRUE,
            ],
            'exclude-patterns' => [],
          ],
        ],
        [
          'src' => [],
          'composer.json' => json_encode([
            'name' => 'drupal/dummy_p1',
            'type' => 'drupal-profile',
          ]),
          'dummy_m1.profile' => '<?php',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunSuccessCollect
   */
  public function testRunSuccessCollect(array $expected, array $vfsStructure, array $options = []): void {
    $vfsRootDirName = $this->getName(FALSE) . '.' . $this->dataName();
    $vfs = vfsStream::setup($vfsRootDirName, NULL, $vfsStructure);

    $options['workingDirectory'] = $vfs->url();

    $result = $this
      ->taskBuilder
      ->taskMarvinPhpcsConfigFallback($options)
      ->setContainer($this->container)
      ->run();

    if (array_key_exists('exitCode', $expected)) {
      static::assertSame($expected['exitCode'], $result->getExitCode());
    }

    if (array_key_exists('assets', $expected)) {
      foreach ($expected['assets'] as $key => $value) {
        static::assertSame(
          $expected['assets'][$key],
          $result[$key],
          "result.assets.$key"
        );
      }
    }
  }

  public function testRunSuccessSkip(): void {
    $expected = [
      'exitCode' => 0,
      'stdOutput' => '',
      'logEntries' => [
        [
          'notice',
          '',
          [
            'name' => 'Marvin - PHP_CodeSniffer config fallback',
          ],
        ],
        [
          'debug',
          'The PHPCS config is already available from state data.',
          [
            'name' => 'Marvin - PHP_CodeSniffer config fallback',
          ],
        ],
      ],
    ];

    $stateData = [
      'my.files' => [
        'a.php' => TRUE,
      ],
      'my.exclude-patterns' => [
        'b.php' => TRUE,
      ],
    ];

    $state = new RoboStateData('', $stateData);

    $task = $this->taskBuilder->taskMarvinPhpcsConfigFallback();
    $task->original()->setState($state);

    $result = $task
      ->setContainer($this->container)
      ->setAssetNamePrefix('my.')
      ->run();

    static::assertSame($expected['exitCode'], $result->getExitCode());
    static::assertSame($stateData['my.files'], $state['my.files']);
    static::assertSame($stateData['my.exclude-patterns'], $state['my.exclude-patterns']);

    if (array_key_exists('logEntries', $expected)) {
      static::assertRoboTaskLogEntries($expected['logEntries'], $task->logger()->cleanLogs());
    }
  }

}

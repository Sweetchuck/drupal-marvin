<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Webmozart\PathUtil\Path;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin\Robo\Task\PrepareDirectoryTask<extended>
 * @covers \Drupal\marvin\Robo\PrepareDirectoryTaskLoader
 */
class PrepareDirectoryTaskTest extends TaskTestBase {

  public function casesRun(): array {
    return [
      'create' => [
        [
          'exitCode' => 0,
          'logEntries' => [
            [
              'notice',
              '{workingDirectory}',
              [
                'workingDirectory' => 'vfs://testRun.create/a',
                'name' => 'Marvin - Prepare directory',
              ],
            ],
            [
              'debug',
              'Create directory: {workingDirectory}',
              [
                'workingDirectory' => 'vfs://testRun.create/a',
                'name' => 'Marvin - Prepare directory',
              ],
            ],
          ],
        ],
        [],
        [
          'workingDirectory' => 'a',
        ],
      ],
      'exists-empty' => [
        [
          'exitCode' => 0,
        ],
        [
          'a' => [],
        ],
        [
          'workingDirectory' => 'a',
        ],
      ],
      'exists-not-empty' => [
        [
          'exitCode' => 0,
          'logEntries' => [
            [
              'notice',
              '{workingDirectory}',
              [
                'workingDirectory' => 'vfs://testRun.exists-not-empty/a',
                'name' => 'Marvin - Prepare directory',
              ],
            ],
            [
              'debug',
              'Delete all content from directory "{workingDirectory}"',
              [
                'workingDirectory' => 'vfs://testRun.exists-not-empty/a',
                'name' => 'Marvin - Prepare directory',
              ],
            ],
          ],
        ],
        [
          'a' => [
            '.git' => [],
            'b.php' => 'b-content',
            '.htaccess' => 'my-htaccess',
          ],
        ],
        [
          'workingDirectory' => 'a',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRun
   */
  public function testRun(array $expected, array $vfsStructure, array $options): void {
    $vfsRootDirName = $this->getName(FALSE) . '.' . $this->dataName();
    $vfs = vfsStream::setup($vfsRootDirName, NULL, $vfsStructure);

    $options['workingDirectory'] = Path::join($vfs->url(), $options['workingDirectory']);

    $task = $this
      ->taskBuilder
      ->taskMarvinPrepareDirectory($options)
      ->setContainer($this->container);

    $result = $task->run();

    static::assertSame($expected['exitCode'], $result->getExitCode());
    static::assertDirectoryExists($options['workingDirectory']);
    static::assertCount(
      2,
      new \DirectoryIterator($options['workingDirectory']),
      sprintf('There are no any items in the "%s" directory', $options['workingDirectory'])
    );

    if (array_key_exists('logEntries', $expected)) {
      static::assertRoboTaskLogEntries($expected['logEntries'], $task->logger()->cleanLogs());
    }
  }

}

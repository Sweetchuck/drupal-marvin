<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask;
use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Webmozart\PathUtil\Path;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask<extended>
 * @covers \Drupal\marvin\Robo\VersionNumberTaskLoader
 */
class VersionNumberBumpExtensionInfoTaskTest extends TaskTestBase {

  public function casesRunSuccess(): array {
    $taskName = 'Marvin - Bump version number - extension info';

    $logEntryHeader = [
      'notice',
      'Bump version number to "<info>{versionNumber}</info>" in "<info>{packagePath}</info>" directory.',
      [
        'versionNumber' => '8.x-1.1',
        'packagePath' => NULL,
        'name' => $taskName,
      ],
    ];

    $logEntrySkipInfoYaml = [
      'debug',
      'Skip update version number to "<info>{versionNumber}</info>" in "<info>{pattern}</info>" files.',
      [
        'versionNumber' => '',
        'pattern' => '',
        'name' => $taskName,
      ],
    ];

    $logEntryDoInfoYaml = [
      'debug',
      'Update version number to "<info>{versionNumber}</info>" in "<info>{file}</info>" file.',
      [
        'versionNumber' => '',
        'file' => '',
        'name' => $taskName,
      ],
    ];

    $logEntrySkipComposerJson = [
      'debug',
      'Skip update version number to "<info>{versionNumber}</info>" in "<info>{file}</info>" file.',
      [
        'versionNumber' => '',
        'file' => '',
        'name' => $taskName,
      ],
    ];

    $logEntryDoComposerJson = [
      'debug',
      'Update version number to "<info>{versionNumber}</info>" in "<info>{file}</info>" file.',
      [
        'versionNumber' => '',
        'file' => '',
        'name' => $taskName,
      ],
    ];

    $composerJsonBefore = implode(PHP_EOL, [
      '{',
      '    "name": "drupal/dummy_m1",',
      '    "version": "1.0.0",',
      '    "type": "drupal-module"',
      '}',
      '',
    ]);

    $composerJsonAfter = implode(PHP_EOL, [
      '{',
      '    "name": "drupal/dummy_m1",',
      '    "version": "1.1.0",',
      '    "type": "drupal-module"',
      '}',
      '',
    ]);

    $infoYamlBefore = implode(PHP_EOL, [
      'type: module',
      'version: 8.x-1.0',
      'core: 8.x',
      '',
    ]);

    $infoYamlAfter = implode(PHP_EOL, [
      'type: module',
      'version: 8.x-1.1',
      'core: 8.x',
      '',
    ]);

    return [
      'all' => [
        [
          'exitCode' => 0,
          'logEntries' => [
            array_replace_recursive($logEntryHeader, [2 => ['packagePath' => 'vfs://testRunSuccess.all']]),
            array_replace_recursive($logEntryDoInfoYaml, [
              2 => [
                'versionNumber' => '8.x-1.1',
                'file' => 'vfs://testRunSuccess.all/dummy_m1.info.yml',
              ],
            ]),
            array_replace_recursive($logEntryDoComposerJson, [
              2 => [
                'versionNumber' => '1.1.0',
                'file' => 'vfs://testRunSuccess.all/composer.json',
              ],
            ]),
          ],
          'files' => [
            'composer.json' => $composerJsonAfter,
            'dummy_m1.info.yml' => $infoYamlAfter,
          ],
        ],
        [
          'composer.json' => $composerJsonBefore,
          'dummy_m1.info.yml' => $infoYamlBefore,
        ],
        [
          'versionNumber' => '8.x-1.1',
        ],
      ],
      'bumpExtensionInfo.false' => [
        [
          'exitCode' => 0,
          'logEntries' => [
            array_replace_recursive($logEntryHeader, [2 => ['packagePath' => 'vfs://testRunSuccess.bumpExtensionInfo.false']]),
            array_replace_recursive($logEntrySkipInfoYaml, [
              2 => [
                'versionNumber' => '8.x-1.1',
                'pattern' => 'vfs://testRunSuccess.bumpExtensionInfo.false/*.info.yml',
              ],
            ]),
            array_replace_recursive($logEntryDoComposerJson, [
              2 => [
                'versionNumber' => '1.1.0',
                'file' => 'vfs://testRunSuccess.bumpExtensionInfo.false/composer.json',
              ],
            ]),
          ],
          'files' => [
            'composer.json' => $composerJsonAfter,
            'dummy_m1.info.yml' => $infoYamlBefore,
          ],
        ],
        [
          'composer.json' => $composerJsonBefore,
          'dummy_m1.info.yml' => $infoYamlBefore,
        ],
        [
          'versionNumber' => '8.x-1.1',
          'bumpExtensionInfo' => FALSE,
        ],
      ],
      'bumpComposerJson.false' => [
        [
          'exitCode' => 0,
          'logEntries' => [
            array_replace_recursive($logEntryHeader, [
              2 => [
                'packagePath' => 'vfs://testRunSuccess.bumpComposerJson.false',
              ],
            ]),
            array_replace_recursive($logEntryDoInfoYaml, [
              2 => [
                'versionNumber' => '8.x-1.1',
                'file' => 'vfs://testRunSuccess.bumpComposerJson.false/dummy_m1.info.yml',
              ],
            ]),
            array_replace_recursive($logEntrySkipComposerJson, [
              2 => [
                'versionNumber' => '1.1.0',
                'file' => 'vfs://testRunSuccess.bumpComposerJson.false/composer.json',
              ],
            ]),
          ],
          'files' => [
            'composer.json' => $composerJsonBefore,
            'dummy_m1.info.yml' => $infoYamlAfter,
          ],
        ],
        [
          'composer.json' => $composerJsonBefore,
          'dummy_m1.info.yml' => $infoYamlBefore,
        ],
        [
          'versionNumber' => '8.x-1.1',
          'bumpComposerJson' => FALSE,
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunSuccess
   */
  public function testRunSuccess(array $expected, array $vfsStructure, array $options): void {
    $expected += [
      'files' => [],
    ];

    $options += [
      'packagePath' => '.',
    ];

    $vfsRootDirName = $this->getName(FALSE) . '.' . $this->dataName();
    $vfs = vfsStream::setup($vfsRootDirName, NULL, $vfsStructure);

    $options['packagePath'] = Path::join($vfs->url(), $options['packagePath']);

    $task = $this
      ->taskBuilder
      ->taskMarvinVersionNumberBumpExtensionInfo($options);

    $result = $task->run();

    static::assertSame($expected['exitCode'], $result->getExitCode());

    if (array_key_exists('logEntries', $expected)) {
      static::assertRoboTaskLogEntries($expected['logEntries'], $task->logger()->cleanLogs());
    }

    foreach ($expected['files'] as $fileName => $fileContent) {
      static::assertStringEqualsFile(
        Path::join($options['packagePath'], $fileName),
        $fileContent
      );
    }
  }

  public function casesRunFail(): array {
    return [
      'packagePath.empty' => [
        [
          'class' => \InvalidArgumentException::class,
          'code' => VersionNumberBumpExtensionInfoTask::ERROR_CODE_PACKAGE_PATH_EMPTY,
        ],
      ],
      'packagePath.not-exists' => [
        [
          'class' => \InvalidArgumentException::class,
          'code' => VersionNumberBumpExtensionInfoTask::ERROR_CODE_PACKAGE_PATH_NOT_EXISTS,
        ],
        [
          'packagePath' => 'a',
        ],
      ],
      'versionNumber.empty' => [
        [
          'class' => \InvalidArgumentException::class,
          'code' => VersionNumberBumpExtensionInfoTask::ERROR_CODE_VERSION_NUMBER_EMPTY,
        ],
        [
          'packagePath' => 'a',
        ],
        [
          'a' => [],
        ],
      ],
      'versionNumber.invalid' => [
        [
          'class' => \InvalidArgumentException::class,
          'code' => VersionNumberBumpExtensionInfoTask::ERROR_CODE_VERSION_NUMBER_INVALID,
        ],
        [
          'packagePath' => 'a',
          'versionNumber' => 'b',
        ],
        [
          'a' => [],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunFail
   */
  public function testRunFail($expected, array $options = [], array $vfsStructure = []): void {
    if (array_key_exists('class', $expected)) {
      $this->expectException($expected['class']);
    }

    if (array_key_exists('message', $expected)) {
      $this->expectExceptionMessage($expected['message']);
    }

    if (array_key_exists('code', $expected)) {
      $this->expectExceptionCode($expected['code']);
    }

    if (!empty($options['packagePath'])) {
      $vfsRootDirName = $this->getName(FALSE) . '.' . $this->dataName();
      $vfs = vfsStream::setup($vfsRootDirName, NULL, $vfsStructure);
      $options['packagePath'] = Path::join($vfs->url(), $options['packagePath']);
    }

    $this
      ->taskBuilder
      ->taskMarvinVersionNumberBumpExtensionInfo($options)
      ->run();
  }

}

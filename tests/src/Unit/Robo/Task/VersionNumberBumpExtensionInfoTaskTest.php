<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask;
use Drupal\Tests\marvin\Unit\TaskTestBase;
use org\bovigo\vfs\vfsStream;
use Webmozart\PathUtil\Path;

/**
 * @covers \Drupal\marvin\Robo\Task\VersionNumberBumpExtensionInfoTask
 * @covers \Drupal\marvin\Robo\VersionNumberTaskLoader
 */
class VersionNumberBumpExtensionInfoTaskTest extends TaskTestBase {

  public function casesRunSuccess(): array {
    $logEntryHeader = [
      'notice',
      'Bump version number to "<info>{versionNumber}</info>" in "<info>{packagePath}</info>" directory.',
      [
        'versionNumber' => '8.x-1.1',
        'packagePath' => NULL,
        'name' => 'Marvin - Bump version number - extension info',
      ],
    ];

    $logEntrySkipInfoYaml = [
      'debug',
      'Skip version number bumping in *.info.yml files.',
      [
        'name' => 'Marvin - Bump version number - extension info',
      ],
    ];

    $logEntrySkipComposerJson = [
      'debug',
      'Skip version number bumping in composer.json.',
      [
        'name' => 'Marvin - Bump version number - extension info',
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
            $logEntrySkipInfoYaml,
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
            array_replace_recursive($logEntryHeader, [2 => ['packagePath' => 'vfs://testRunSuccess.bumpComposerJson.false']]),
            $logEntrySkipComposerJson,
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

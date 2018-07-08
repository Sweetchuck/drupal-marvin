<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\marvin\Robo\Task\CopyFilesTask;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Robo\Robo;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Debug\BufferingLogger;
use Symfony\Component\Finder\SplFileInfo;

class CopyFilesTaskTest extends TestCase {

  public function casesRunSuccess(): array {
    $fileContent = 'a';

    $logEntry = [
      'debug',
      'copy: {srcDir} {dstDir} {file}',
      [
        'srcDir' => 'vfs://testRunSuccess/mySrc',
        'dstDir' => 'vfs://testRunSuccess/myDst',
        'file' => NULL,
        'name' => 'Marvin - Copy files',
      ],
    ];

    return [
      'empty' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'logs' => [
            [
              'notice',
              '',
              [
                'name' => 'Marvin - Copy files',
              ],
            ],
          ],
        ],
        [],
        [],
      ],
      'basic' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'logs' => [
            [
              'notice',
              '',
              [
                'name' => 'Marvin - Copy files',
              ],
            ],
            array_replace_recursive($logEntry, [2 => ['file' => 'a.txt']]),
            array_replace_recursive($logEntry, [2 => ['file' => 'b/c.txt']]),
            array_replace_recursive($logEntry, [2 => ['file' => 'd.txt']]),
          ],
          'files' => [
            'a.txt' => TRUE,
            'b/c.txt' => TRUE,
            'b/d.txt' => FALSE,
            'd.txt' => TRUE,
          ],
        ],
        [
          'mySrc' => [
            'a.txt' => $fileContent,
            'b' => [
              'c.txt' => $fileContent,
              'd.txt' => $fileContent,
            ],
            'd.txt' => $fileContent,
          ],
        ],
        [
          'srcDir' => 'mySrc',
          'dstDir' => 'myDst',
          'files' => [
            'a.txt',
            'b/c.txt',
          ],
          'filesSpl' => [
            'd.txt',
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunSuccess
   */
  public function testRunSuccess(array $expected, array $structure, array $options): void {
    $container = Robo::createDefaultContainer();
    Robo::setContainer($container);

    $vfs = vfsStream::setup(__FUNCTION__, NULL, $structure);
    $rootDir = $vfs->url();
    if (!empty($options['srcDir'])) {
      $options['srcDir'] = $rootDir . '/' . $options['srcDir'];
    }

    if (!empty($options['dstDir'])) {
      $options['dstDir'] = $rootDir . '/' . $options['dstDir'];
    }

    if (array_key_exists('filesSpl', $options)) {
      foreach ($options['filesSpl'] as $relativePathName) {
        $options['files']['spl'][] = new SplFileInfo("{$options['srcDir']}/$relativePathName", dirname($relativePathName), $relativePathName);
      }

      unset($options['filesSpl']);
    }

    $mainStdOutput = new BufferedOutput();
    $logger = new BufferingLogger();
    $task = new CopyFilesTask();
    $task->setLogger($logger);

    $result = $task
      ->setOptions($options)
      ->setOutput($mainStdOutput)
      ->run();

    if (array_key_exists('exitCode', $expected)) {
      $this->assertSame($expected['exitCode'], $result->getExitCode());
    }

    if (array_key_exists('stdOutput', $expected)) {
      $this->assertSame($expected['stdOutput'], $mainStdOutput->fetch());
    }

    if (array_key_exists('logs', $expected)) {
      $actualLogs = $logger->cleanLogs();
      $this->assertSame(
        count($expected['logs']),
        count($actualLogs),
        'Number of log messages'
      );
      for ($i = 0; $i < count($actualLogs); $i++) {
        $log = $actualLogs[$i];
        unset($log[2]['task']);
        $this->assertSame($expected['logs'][$i], $log);
      }
    }

    if (!empty($expected['files'])) {
      foreach ($expected['files'] as $fileName => $exists) {
        $fileNameAbsolute = "{$options['dstDir']}/$fileName";
        $this->assertSame(
          $exists,
          file_exists($fileNameAbsolute),
          sprintf('file exists: %s; file name: "%s";', var_export($exists), $fileNameAbsolute)
        );
      }
    }
  }

}

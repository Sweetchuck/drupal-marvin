<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\Tests\marvin\Unit\TaskTestBase;

/**
 * @group marvin
 * @group robo-task
 *
 * @covers \Drupal\marvin\Robo\Task\GitCommitMsgValidatorTask<extended>
 * @covers \Drupal\marvin\Robo\GitCommitMsgValidatorTaskLoader
 */
class GitCommitMsgValidatorTaskTest extends TaskTestBase {

  public function casesRunSuccess(): array {
    return [
      'basic' => [
        [
          'exitCode' => 0,
          'stdOutput' => '',
          'stdError' => '',
        ],
        [
          'fileName' => $this->getDataBase64FileNameFromLines([
            '# My comment',
            'My subject',
            '',
          ]),
          'rules' => [
            'subjectLine' => [
              'pattern' => '/^My .+/u',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunSuccess
   */
  public function testRunSuccess(array $expected, array $options): void {
    $task = $this
      ->taskBuilder
      ->taskMarvinGitCommitMsgValidator($options)
      ->setContainer($this->container);

    $result = $task->run();

    if (array_key_exists('exitCode', $expected)) {
      static::assertSame($expected['exitCode'], $result->getExitCode());
    }

    /** @var \Drupal\Tests\marvin\Helper\DummyOutput $stdOutput */
    $stdOutput = $this->container->get('output');
    if (array_key_exists('stdOutput', $expected)) {
      static::assertSame($expected['stdOutput'], $stdOutput->output);
    }

    if (array_key_exists('stdError', $expected)) {
      static::assertSame($expected['stdError'], $stdOutput->getErrorOutput()->output);
    }
  }

  protected function getDataBase64FileNameFromLines(array $lines): string {
    return 'data://text/plain;base64,' . base64_encode(implode(PHP_EOL, $lines));
  }

}

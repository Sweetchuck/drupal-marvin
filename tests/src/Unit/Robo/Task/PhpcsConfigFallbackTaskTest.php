<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use org\bovigo\vfs\vfsStream;
use Robo\State\Data as RoboStateData;

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
      $this->assertSame($expected['exitCode'], $result->getExitCode());
    }

    if (array_key_exists('assets', $expected)) {
      foreach ($expected['assets'] as $key => $value) {
        $this->assertSame(
          $expected['assets'][$key],
          $result[$key],
          "result.assets.$key"
        );
      }
    }
  }

  public function testRunSuccessSkip(): void {
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

    $this->assertSame(0, $result->getExitCode());
    $this->assertSame($stateData['my.files'], $state['my.files']);
    $this->assertSame($stateData['my.exclude-patterns'], $state['my.exclude-patterns']);

    /** @var \Drupal\Tests\marvin\Helper\DummyOutput $output */
    $output = $this->container->get('output');
    $this->assertContains(
      'The PHPCS config is already available from state data.',
      $output->getErrorOutput()->output
    );
  }

}

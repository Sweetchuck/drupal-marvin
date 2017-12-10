<?php

namespace Drush\Commands\marvin\Tests\Unit\Robo\Task;

use Drush\marvin\Robo\Task\ArtifactCollectFilesTask;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Robo\Config\Config;
use Robo\Robo;
use Stringy\StaticStringy;
use Symfony\Component\Console\Output\BufferedOutput;

class ReleaseCollectFilesTaskTest extends TestCase {

  public function casesRunSuccess(): array {
    $cases = [];
    foreach (['module', 'theme', 'drush', 'profile'] as $projectType) {
      $files = $this->getDrupalExtensionFiles('dummy_m1', $projectType);
      $cases["$projectType.basic"] = [$files['expected'], $files['structure']];
    }

    return $cases;
  }

  /**
   * @dataProvider casesRunSuccess
   */
  public function testRunSuccess(array $expected, array $structure): void {
    $container = Robo::createDefaultContainer();
    Robo::setContainer($container);

    $vfs = vfsStream::setup(__FUNCTION__, NULL, $structure);
    $mainStdOutput = new BufferedOutput();
    $config = new Config([
      'command' => [
        'marvin' => [
          'settings' => [
            'buildDir' => 'build',
          ],
        ],
      ],
    ]);

    $task = new ArtifactCollectFilesTask();
    $task
      ->setOptions([
        'composerJsonFileName' => 'composer.json',
        'packagePath' => $vfs->url(),
      ])
      ->setLogger($container->get('logger'));

    $result = $task
      ->setConfig($config)
      ->setOutput($mainStdOutput)
      ->run();

    $actual = [];
    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($result['files'] as $files) {
      foreach ($files as $file) {
        $actual[] = $file->getRelativePathname();
      }
    }

    sort($expected);
    sort($actual);
    $this->assertEquals($expected, $actual);
  }

  protected function getDrupalExtensionFiles(string $name, string $type): array {
    $nameUpperCamel = StaticStringy::upperCamelize($name);
    $fileContent = 'a';

    $mainFileName = $type === 'drush' ? "$name.module" : "$name.$type";

    return [
      'expected' => [
        'config/install/a.yml',
        'config/install/b.yml',
        'config/optional/c.yml',
        'config/optional/d.yml',
        "src/Entity/$nameUpperCamel.php",
        "src/Entity/{$nameUpperCamel}Interface.php",
        "src/Tests/{$nameUpperCamel}TestBase.php",
        "src/Tests/{$nameUpperCamel}Test.php",
        'templates/e.html.twig',
        'tests/src/Functional/f.php',
        'tests/src/Kernel/k.php',
        'tests/src/Unit/u.php',
        'css/main.css',
        'css/foo.md',
        'js/main.js',
        'js/main.td.ts',
        "$name.info.yml",
        $mainFileName,
        "$name.token.inc",
        "$name.theme.inc",
        'composer.json',
        'README.md',
      ],
      'structure' => array_replace_recursive(
        $this->getStructureOfTheUndesirableFiles(),
        [
          'config' => [
            'install' => [
              'a.yml' => $fileContent,
              'b.yml' => $fileContent,
            ],
            'optional' => [
              'c.yml' => $fileContent,
              'd.yml' => $fileContent,
            ],
          ],
          'src' => [
            'Entity' => [
              "$nameUpperCamel.php" => $fileContent,
              "{$nameUpperCamel}Interface.php" => $fileContent,
            ],
            'Tests' => [
              "{$nameUpperCamel}TestBase.php" => $fileContent,
              "{$nameUpperCamel}Test.php" => $fileContent,
            ],
          ],
          'templates' => [
            'e.html.twig' => $fileContent,
          ],
          'tests' => [
            'src' => [
              'Functional' => [
                'f.php' => $fileContent,
              ],
              'Kernel' => [
                'k.php' => $fileContent,
              ],
              'Unit' => [
                'u.php' => $fileContent,
              ],
            ],
          ],
          'css' => [
            'main.css' => $fileContent,
            'foo.md' => $fileContent,
          ],
          'js' => [
            'main.js' => $fileContent,
            'main.td.ts' => $fileContent,
          ],
          "$name.info.yml" => $fileContent,
          $mainFileName => $fileContent,
          "$name.token.inc" => $fileContent,
          "$name.theme.inc" => $fileContent,
          'README.md' => $fileContent,
          'composer.json' => json_encode([
            'type' => "drupal-$type",
          ]),
        ]
      ),
    ];
  }

  protected function getStructureOfTheUndesirableFiles(): array {
    $fileContent = 'a';
    $dirContent = [
      'a.php' => $fileContent,
      'vcs.xml' => $fileContent,
    ];

    return [
      '.idea' => $dirContent,
      '.phpstorm.meta.php' => $fileContent,
      'test.___jb_old___' => $fileContent,
      '.kdev4' => $dirContent,
      'my.kdev4' => $fileContent,
      '.DS_Store' => $fileContent,
      '.directory' => $fileContent,
      'css' => [
        'main.scss' => $fileContent,
        'main.sass' => $fileContent,
        'main.css.map' => $fileContent,
      ],
      'js' => [
        'main.ts' => $fileContent,
        'main.js.map' => $fileContent,
      ],
      // @todo Add more files.
    ];
  }

}

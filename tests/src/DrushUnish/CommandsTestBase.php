<?php

namespace Drush\Commands\Tests\marvin\Unish;

use Unish\CommandUnishTestCase;
use Webmozart\PathUtil\Path;

abstract class CommandsTestBase extends CommandUnishTestCase {

  /**
   * @var string
   */
  protected $marvinDir = '';

  public function __construct($name = NULL, array $data = [], string $dataName = '') {
    parent::__construct($name, $data, $dataName);

    $this->initMarvinDir();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if (!$this->getSites()) {
      $this->setUpDrupal(1, TRUE);
    }

    parent::setUp();
    $this->deleteTestArtifacts();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->deleteTestArtifacts();
    parent::tearDown();
  }

  /**
   * Clean .phpstorm.meta.php directory.
   */
  protected function deleteTestArtifacts() {
    return $this;
  }

  protected function initMarvinDir() {
    $this->marvinDir = Path::canonicalize(Path::join(__DIR__, '..', '..', '..'));

    return $this;
  }

  protected function getDefaultDrushCommandOptions(): array {
    return [
      'root' => $this->webroot(),
      'uri' => key(static::getSites()),
      'yes' => NULL,
      'no-ansi' => NULL,
      'config' => Path::join(static::getSut(), 'drush'),
    ];
  }

}

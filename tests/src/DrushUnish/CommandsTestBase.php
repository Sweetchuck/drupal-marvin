<?php

namespace Drush\Commands\Tests\marvin\Unish;

use Unish\CommandUnishTestCase;
use Webmozart\PathUtil\Path;

abstract class CommandsTestBase extends CommandUnishTestCase {

  /**
   * @var string
   */
  protected static $marvinDir = '';

  /**
   * {@inheritdoc}
   */
  public static function getDrush() {
    return Path::join(static::getMarvinDir(), 'bin', 'drush');
  }

  /**
   * {@inheritdoc}
   *
   * Replace self::getDrush() with static::getDrush().
   */
  public function drush($command, array $args = [], array $options = [], $site_specification = NULL, $cd = NULL, $expected_return = self::EXIT_SUCCESS, $suffix = NULL, $env = []) {
    // Cd is added for the benefit of siteSshTest which tests a strict command.
    $global_option_list = [
      'simulate',
      'root',
      'uri',
      'include',
      'config',
      'alias-path',
      'ssh-options',
      'backend',
      'cd',
    ];

    $options += ['uri' => 'dev'];
    $hide_stderr = FALSE;
    $cmd = [
      self::getDrush(),
    ];

    // Insert global options.
    foreach ($options as $key => $value) {
      if (in_array($key, $global_option_list)) {
        unset($options[$key]);
        if ($key == 'backend') {
          $hide_stderr = TRUE;
          $value = NULL;
        }

        if ($key == 'uri' && $value == 'OMIT') {
          continue;
        }

        if (!isset($value)) {
          $cmd[] = "--$key";
        }
        else {
          $cmd[] = "--$key=" . self::escapeshellarg($value);
        }
      }
    }

    if ($level = $this->logLevel()) {
      $cmd[] = '--' . $level;
    }
    $cmd[] = "--no-interaction";

    // Insert code coverage argument before command, in order for it to be
    // parsed as a global option. This matters for commands like ssh and rsync
    // where options after the command are passed along to external commands.
    $result = $this->getTestResultObject();
    if ($result->getCollectCodeCoverageInformation()) {
      $coverage_file = tempnam($this->getTmp(), 'drush_coverage');
      if ($coverage_file) {
        $cmd[] = "--drush-coverage=" . $coverage_file;
      }
    }

    // Insert site specification and drush command.
    $cmd[] = empty($site_specification)
      ? NULL
      : self::escapeshellarg(
        $site_specification
      );
    $cmd[] = $command;

    // Insert drush command arguments.
    foreach ($args as $arg) {
      $cmd[] = self::escapeshellarg($arg);
    }

    // Insert drush command options.
    foreach ($options as $key => $value) {
      if (!isset($value)) {
        $cmd[] = "--$key";
      }
      else {
        $cmd[] = "--$key=" . self::escapeshellarg($value);
      }
    }

    $cmd[] = $suffix;
    if ($hide_stderr) {
      $cmd[] = '2>' . $this->bitBucket();
    }

    // Remove NULLs.
    $exec = array_filter($cmd, 'strlen');

    // Set sendmail_path to 'true' to disable any outgoing emails
    // that tests might cause Drupal to send.
    $php_options = (array_key_exists('PHP_OPTIONS', $env)) ?
      $env['PHP_OPTIONS'] . ' ' : '';

    // @todo The PHP Options below are not yet honored by execute().
    // See .travis.yml for an alternative way.
    $env['PHP_OPTIONS'] = "{$php_options}-d sendmail_path='true'";
    $cmd = implode(' ', $exec);
    $return = $this->execute($cmd, $expected_return, $cd, $env);

    // Save code coverage information.
    if (!empty($coverage_file)) {
      $data = unserialize(file_get_contents($coverage_file));
      unlink($coverage_file);
      // Save for appending after the test finishes.
      $this->coverage_data[] = $data;
    }

    return $return;
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

  protected static function getMarvinDir(): string {
    if (static::$marvinDir === '') {
      static::$marvinDir = Path::canonicalize(
        Path::join(__DIR__, '..', '..', '..')
      );
    }

    return static::$marvinDir;
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

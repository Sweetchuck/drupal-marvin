<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Integration;

/**
 * @group marvin
 * @group drush-command
 *
 * @covers \Drush\Commands\marvin\GitHookCommandsBase<extended>
 */
class GitHookCommandsTest extends UnishIntegrationTestCase {

  public function testMarvinGitHookPreCommit(): void {
    $this->drush(
      'marvin:git-hook:pre-commit',
      [],
      [],
      0
    );

    $actualStdOutput = $this->getOutput();
    $actualStdError = $this->getErrorOutput();

    static::assertSame('', $actualStdError, 'StdError');
    static::assertSame('GitHookSubscriberCommands::onEventMarvinGitHookPreCommit called', $actualStdOutput, 'StdOutput');
  }

}

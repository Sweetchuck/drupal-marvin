<?php

declare(strict_types=1);

namespace Drupal\marvin\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Drush command annotator.
 *
 * @code
 * use Drupal\marvin\Attributes\PreCommandInitLintReporters as PreCommandMarvinInitLintReporters;
 *
 * #[MarvinCli\PreCommandInitLintReporters()]
 * @endcode
 *
 * @deprecated
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class PreCommandInitLintReporters {

  public const string SELECTOR = 'pre-command-marvin-init-lint-reporters';

  /**
   * @phpstan-param \ReflectionAttribute<\Robo\Tasks> $attribute
   */
  public static function handle(
    \ReflectionAttribute $attribute,
    CommandInfo $commandInfo,
  ): void {
    $commandInfo->addAnnotation(static::SELECTOR, []);
  }

}

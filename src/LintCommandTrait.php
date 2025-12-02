<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Psr\Container\ContainerInterface;
use Sweetchuck\LintReport\ReporterInterface;
use Sweetchuck\Utils\Filter\EnabledFilter;

/**
 * @property \Drupal\marvin\Utils $utils
 * @property \Drush\Config\DrushConfig $drushConfig
 */
trait LintCommandTrait {

  abstract protected function getContainer(): ContainerInterface;

  /**
   * @return array{id: string}
   *
   * @todo More properties. (label, description).
   */
  abstract protected function getLinterInfo(): array;

  /**
   * @deprecated
   *
   * @see \Drupal\marvin\Utils::getConditionContext
   * @see \Drupal\marvin\Utils::getFirstByConditions
   */
  protected function getPresetNameByEnvironmentVariant(): string {
    $environmentVariants = $this->getEnvironmentVariants();
    $defaultPresetMapping = $this->getConfigValue('defaultPreset');
    foreach ($environmentVariants as $environmentVariant) {
      if (!empty($defaultPresetMapping[$environmentVariant])) {
        return $defaultPresetMapping[$environmentVariant];
      }
    }

    // @todo Check if it exists.
    // @todo Choice the first one if there is no default.
    return 'default';
  }

  /**
   * @deprecated
   *
   * @return string[]
   */
  protected function getLintReporterConfigNamesByEnvironmentVariant(): array {
    $reporterCombinations = (array) $this
      ->drushConfig
      ->get('marvin.lint.reporterCombination');

    $filter = new EnabledFilter();
    foreach ($this->getEnvironmentVariants() as $environmentVariant) {
      if (isset($reporterCombinations[$environmentVariant])) {
        return array_keys(array_filter($reporterCombinations[$environmentVariant], $filter));
      }
    }

    return [];
  }

  /**
   * @phpstan-return array<string, \Sweetchuck\LintReport\ReporterInterface>
   */
  protected function getLintReporters(array $context): array {
    $conditionContext = $this->utils->getConditionContext($this->drushConfig);
    $reporterSetCandidates = (array) $this
      ->drushConfig
      ->get('marvin.lint.reporterSetCandidates', []);
    $reporterSetName = $this->utils->getFirstByConditions($reporterSetCandidates, $conditionContext);
    $reporterSet = $reporterSetCandidates[$reporterSetName] ?? [];
    $reporterSet += ['reporters' => ['verboseStdOutput' => TRUE]];
    $reporterNames = array_keys($reporterSet['reporters'], TRUE, TRUE);

    $reporterConfigs = array_intersect_key(
      $this->drushConfig->get('marvin.lint.reporters', []),
      array_flip($reporterNames),
    );

    return $this->parseLintReporterConfigs($context, $reporterConfigs);
  }

  /**
   * @phpstan-param array<string, marvin-lint-reporter-config-base> $lintReporterConfigs
   *
   * @phpstan-return array<string, \Sweetchuck\LintReport\ReporterInterface>
   */
  protected function parseLintReporterConfigs(array $context, array $lintReporterConfigs): array {
    $reporters = [];
    foreach ($lintReporterConfigs as $configId => $config) {
      if (!is_array($config)) {
        $config = ['service' => $config];
      }

      $reporters[$configId] = $this->parseLintReporterConfig($context, $config);
    }

    return $reporters;
  }

  /**
   * @phpstan-param marvin-lint-reporter-config-base $config
   */
  protected function parseLintReporterConfig(array $context, array $config): ReporterInterface {
    $config['options']['basePath'] = $this->utils->getProjectRootDir($this->drushConfig);

    $this->processReporterConfig($context, $config);

    /** @var \Sweetchuck\LintReport\ReporterInterface $reporter */
    $reporter = $this->getContainer()->get($config['service']);
    $reporter->setOptions($config['options']);

    return $reporter;
  }

  protected function processReporterConfig(array $context, array &$config): static {
    $linterInfo = $this->getLinterInfo();
    $replacementPairs = [
      '{{ linter.id }}' => $linterInfo['id'],
      '{{ reportsDir }}' => $context['reportsDir'] ?: '.',
    ];

    if (!empty($config['options']['destination'])) {
      $config['options']['destination'] = strtr(
        $config['options']['destination'],
        $replacementPairs,
      );
    }

    return $this;
  }

}

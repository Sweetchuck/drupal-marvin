<?php

namespace Drush\Commands\marvin;

use League\Container\ContainerInterface;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\ReporterInterface;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

class LintCommandsBase extends CommandsBase {

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':lint';
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    if (!$container->has('lintCheckstyleReporter')) {
      BaseReporter::lintReportConfigureContainer($container);
    }

    return parent::setContainer($container);
  }

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
   * @return string[]
   */
  protected function getLintReporterConfigNamesByEnvironmentVariant(): array {
    $reporterCombinations = $this
      ->getConfig()
      ->get('marvin.lint.reporterCombination', []);

    $filter = new ArrayFilterEnabled();
    foreach ($this->getEnvironmentVariants() as $environmentVariant) {
      if (isset($reporterCombinations[$environmentVariant])) {
        return array_keys(array_filter($reporterCombinations[$environmentVariant], $filter));
      }
    }

    return [];
  }

  protected function getLintReporters(): array {
    $lintReporterConfigs = $this->getConfig()->get('marvin.lint.reporterConfig', []);
    $lintReporterConfigNames = $this->getLintReporterConfigNamesByEnvironmentVariant();

    $selectedLintReporterConfigs = array_intersect_key(
      $lintReporterConfigs,
      array_flip($lintReporterConfigNames)
    );

    return $this->parseLintReporterConfigs($selectedLintReporterConfigs);
  }

  /**
   * @return \Sweetchuck\LintReport\ReporterInterface[]
   */
  protected function parseLintReporterConfigs(array $lintReporterConfigs): array {
    $reporters = [];
    foreach ($lintReporterConfigs as $configId => $config) {
      if (!is_array($config)) {
        $config = ['service' => $config];
      }

      $reporters[$configId] = $this->parseLintReporterConfig($config);
    }

    return $reporters;
  }

  protected function parseLintReporterConfig(array $config): ReporterInterface {
    $config['options']['basePath'] = $this->getProjectRootDir();

    /** @var \Sweetchuck\LintReport\ReporterInterface $reporter */
    $reporter = $this->getContainer()->get($config['service']);
    $reporter->setOptions($config['options']);

    return $reporter;
  }

}

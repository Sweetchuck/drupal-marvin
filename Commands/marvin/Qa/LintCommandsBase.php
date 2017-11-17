<?php

namespace Drush\Commands\marvin\Qa;

use Drush\Commands\marvin\QaCommandsBase;
use League\Container\ContainerInterface;
use Stringy\StaticStringy;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\ReporterInterface;
use Sweetchuck\Robo\Git\Utils;

class LintCommandsBase extends QaCommandsBase {

  /**
   * @var array
   */
  protected $cliArgs = [];

  /**
   * @var array
   */
  protected $cliOptions = [];

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    BaseReporter::lintReportConfigureContainer($container);

    return parent::setContainer($container);
  }

  protected function getPresetNameByEnvironmentVariant(): string {
    $config = $this->getConfig();
    $environment = $config->get('command.marvin.settings.environment');
    $gitHook = $config->get('command.marvin.settings.gitHook');

    $environmentVariants = [$environment];

    if ($environment === 'dev' && $gitHook) {
      array_unshift(
        $environmentVariants,
        StaticStringy::camelize("$environment-$gitHook")
      );
    }

    foreach ($environmentVariants as $environmentVariant) {
      $presetName = $this->getConfigValue("defaultPreset.$environmentVariant");
      if ($presetName !== NULL) {
        return $presetName;
      }
    }

    return 'default';
  }

  /**
   * @return string[]
   */
  protected function getLintReporterConfigNamesByEnvironmentVariant(): array {
    $config = $this->getConfig();
    $environmentVariants = $this->getEnvironmentVariants();

    $reporterCombinations = $config->get('command.marvin.qa.lint.settings.reporterCombination');
    foreach ($environmentVariants as $environmentVariant) {
      if (isset($reporterCombinations[$environmentVariant])) {
        return Utils::filterEnabled($reporterCombinations[$environmentVariant]);
      }
    }

    return [];
  }

  protected function getLintReporters(): array {
    $config = $this->getConfig();
    $lintReporterConfigs = $config->get('command.marvin.qa.lint.settings.reporterConfig');
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
    /** @var \Sweetchuck\LintReport\ReporterInterface $reporter */
    $reporter = $this->getContainer()->get($config['service']);

    // @todo Support "setOptions()" by the ReporterInterface.
    if (!empty($config['options']) && method_exists($reporter, 'setOptions')) {
      $reporter->setOptions($config['options']);
    }

    return $reporter;
  }

}

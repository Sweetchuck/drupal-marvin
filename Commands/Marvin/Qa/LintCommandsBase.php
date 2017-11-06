<?php

namespace Drush\Commands\Marvin\Qa;

use Drush\Commands\Marvin\QaCommandsBase;
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

  /**
   * @return string[]
   */
  protected function lintGetSupportedProjectTypes(): array {
    return [
      'project',
      'drupal-project',
      'drupal-drush',
    ];
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

  protected function getLintReporters(string $environment): array {
    $config = $this->getConfig();
    $lintSettings = $config->get('command.marvin.qa.lint.settings');
    $reporterPresetNames = $lintSettings['defaultReporterPreset'][$environment];
    $reporterPresetNames = Utils::filterEnabled($reporterPresetNames);
    $presets = array_intersect_key($lintSettings['reporterPreset'], array_flip($reporterPresetNames));

    return $this->parseLintReporterPresets($presets);
  }

  /**
   * @return \Sweetchuck\LintReport\ReporterInterface[]
   */
  protected function parseLintReporterPresets(array $presets): array {
    $reporters = [];
    foreach ($presets as $presetId => $preset) {
      if (!is_array($preset)) {
        $preset = ['service' => $preset];
      }

      $reporters[$presetId] = $this->parseLintReporterPreset($preset);
    }

    return $reporters;
  }

  protected function parseLintReporterPreset(array $preset): ReporterInterface {
    /** @var \Sweetchuck\LintReport\ReporterInterface $reporter */
    $reporter = $this->getContainer()->get($preset['service']);
    // @todo Support "setOptions()" by the ReporterInterface.
    if (isset($preset['options']) && method_exists($reporter, 'setOptions')) {
      $reporter->setOptions($preset['options']);
    }

    return $reporter;
  }

}

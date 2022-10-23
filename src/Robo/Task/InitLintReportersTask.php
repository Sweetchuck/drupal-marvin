<?php

declare(strict_types = 1);

namespace Drupal\marvin\Robo\Task;

use Drupal\marvin\Utils;
use Sweetchuck\LintReport\Reporter\BaseReporter;

class InitLintReportersTask extends BaseTask {

  protected ?array $lintReporters = NULL;

  public function getLintReporters(): ?array {
    return $this->lintReporters;
  }

  public function setLintReporters(?array $lintReporters): static {
    $this->lintReporters = $lintReporters;

    return $this;
  }

  protected function getLintReportersWithFallback(): array {
    $lintReporters = $this->getLintReporters();

    return $lintReporters ?? BaseReporter::getServices();
  }

  public function runAction():static {
    Utils::initLintReporters(
     $this->getLintReportersWithFallback(),
      $this->getContainer(),
    );

    return $this;
  }

}

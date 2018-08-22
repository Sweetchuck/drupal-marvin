<?php

namespace Drush\Commands\marvin\StatusReport;

use Drush\Commands\marvin\CommandsBase;

class StatusReportCommandsBase extends CommandsBase {

  /**
   * {@inheritdoc}
   */
  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':status-report';
  }

}

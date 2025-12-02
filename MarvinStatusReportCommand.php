<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Drupal\marvin\ContainerInitializer;
use Drupal\marvin\StatusReport\StatusReport;
use Drupal\marvin\StatusReport\StatusReportEvent;
use Drupal\marvin\Utils;
use Drush\Attributes\Bootstrap as CliBootstrap;
use Drush\Attributes\DefaultFields as CliDefaultFields;
use Drush\Attributes\FieldLabels as CliFieldLabels;
use Drush\Attributes\Formatter as CliFormatter;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\AutowireTrait;
use Drush\Formatters\DrushFormatterManager;
use Drush\Formatters\FormatterTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
  name: self::NAME,
  description: 'Displays Marvin related status report entries.',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
#[CliFormatter(
  returnType: 'array',
  defaultFormatter: 'yaml',
)]
#[CliDefaultFields(
  fields: [
    'severityName',
    'title',
    'value',
    'description',
  ],
)]
#[CliFieldLabels(
  labels: [
    'id' => 'ID',
    'title' => 'Title',
    'value' => 'Value',
    'description' => 'Description',
    'severity' => 'Severity ID',
    'severityName' => 'Severity',
  ],
)]
final class MarvinStatusReportCommand extends Command {

  use AutowireTrait {
    create as protected autowireCreate;
  }
  use FormatterTrait;

  public const string NAME = 'marvin:status-report';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    ContainerInitializer::initialize($container);

    return self::autowireCreate($container);
  }

  public function __construct(
    #[Autowire('formatterManager')]
    protected DrushFormatterManager $formatterManager,
    #[Autowire('eventDispatcher')]
    protected EventDispatcherInterface $eventDispatcher,
    #[Autowire(Utils::class)]
    protected Utils $utils,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $statusReport = new StatusReport($this->utils);

    /* @noinspection PhpExpressionResultUnusedInspection */
    $this->eventDispatcher->dispatch(
      new StatusReportEvent($statusReport),
      StatusReportEvent::EVENT_COLLECT,
    );

    /* @noinspection PhpExpressionResultUnusedInspection */
    $this->eventDispatcher->dispatch(
      new StatusReportEvent($statusReport),
      StatusReportEvent::EVENT_ALTER,
    );

    $this->formatterManager->write(
      $output,
      $input->getOption('format'),
      $statusReport,
      $this
        ->getFormatterOptions()
        ->setInput($input)
        ->setOptions($input->getOptions()),
    );

    return $statusReport->getExitCode();
  }

}

<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Drupal\marvin\ContainerInitializer;
use Drupal\marvin\RuntimeEnvironment\Handler as RteHandler;
use Drupal\marvin\RuntimeEnvironment\RuntimeEnvironmentEvent;
use Drupal\marvin\Utils;
use Drush\Attributes\Bootstrap as CliBootstrap;
use Drush\Attributes\DefaultFields as CliDefaultFields;
use Drush\Attributes\FieldLabels as CliFieldLabels;
use Drush\Attributes\Formatter as CliFormatter;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\AutowireTrait;
use Drush\Config\DrushConfig;
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
  description: 'Lists all the runtime environments.',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
#[CliFormatter(
  returnType: 'marvin_runtime_environment_list',
  defaultFormatter: 'yaml',
)]
#[CliDefaultFields(
  fields: [
    'id',
    'provider',
    'title',
    'description',
  ],
)]
#[CliFieldLabels(
  labels: [
    'id' => 'ID',
    'provider' => 'Provider',
    'title' => 'Title',
    'description' => 'Description',
    'weight' => 'weight',
  ],
)]
final class MarvinRuntimeEnvironmentListCommand extends Command {

  use AutowireTrait {
    create as protected autowireCreate;
  }
  use FormatterTrait;

  public const string NAME = 'marvin:runtime-environment:list';

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
    #[Autowire('config')]
    protected DrushConfig $drushConfig,
    #[Autowire('eventDispatcher')]
    protected EventDispatcherInterface $eventDispatcher,
    #[Autowire(RteHandler::class)]
    protected RteHandler $rteHandler,
    #[Autowire(Utils::class)]
    protected Utils $utils,
  ) {
    parent::__construct();

    $this->eventDispatcher->addListener(
      RuntimeEnvironmentEvent::EVENT_COLLECT,
      $this->onEventMarvinRuntimeEnvironmentListCollect(...),
    );
  }

  public function execute(InputInterface $input, OutputInterface $output): int {
    $this->formatterManager->write(
      $output,
      $input->getOption('format'),
      $this->rteHandler->getList(),
      $this
        ->getFormatterOptions()
        ->setInput($input)
        ->setOptions($input->getOptions()),
    );

    return self::SUCCESS;
  }

  public function onEventMarvinRuntimeEnvironmentListCollect(RuntimeEnvironmentEvent $event): void {
    $root = $this->utils->getProjectRootDir($this->drushConfig);
    $config = $this->drushConfig->get('marvin.runtime_environments') ?: [];

    if (!empty($config['host']['enabled'])) {
      $rte = array_replace_recursive(
        [
          'id' => 'host',
          'weight' => -99,
          // @todo Store the sites somewhere else,
          // because sites aren't change when the runtime_environment changes.
          'description' => 'Uses the host machine without any virtualization',
        ],
        $config['host'],
      );
      $event->runtimeEnvironmentList->offsetSet('host', $rte);
    }

    if (!empty($config['ddev']['enabled'])
      && file_exists("$root/.ddev/config.yaml")
    ) {
      $rte = array_replace_recursive(
        [
          'id' => 'ddev',
          'description' => 'Runtime environment provided by DDev',
        ],
        $config['ddev'],
      );
      $event->runtimeEnvironmentList->offsetSet('ddev', $rte);
    }
  }

}

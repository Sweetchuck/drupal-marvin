<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Drupal\marvin\ContainerInitializer;
use Drupal\marvin\MarvinTaskDefinitionCommandTrait;
use Drupal\marvin\RuntimeEnvironment\CommandEvent as RteCommandEvent;
use Drupal\marvin\RuntimeEnvironment\Handler as RteHandler;
use Drupal\marvin\Utils;
use Drush\Attributes\Bootstrap as CliBootstrap;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\AutowireTrait;
use Drush\Config\DrushConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Robo\Contract\BuilderAwareInterface;
use Robo\TaskAccessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
  name: self::NAME,
  description: 'Updates the configurations to use another runtime environment.',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
final class MarvinRuntimeEnvironmentSwitchCommand extends Command implements BuilderAwareInterface {

  use AutowireTrait {
    create as protected autowireCreate;
  }
  use TaskAccessor;
  use MarvinTaskDefinitionCommandTrait;

  public const string NAME = 'marvin:runtime-environment:switch';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    ContainerInitializer::initialize($container);

    return self::autowireCreate($container);
  }

  public function __construct(
    #[Autowire('config')]
    protected DrushConfig $drushConfig,
    #[Autowire('eventDispatcher')]
    protected EventDispatcherInterface $eventDispatcher,
    #[Autowire(RteHandler::class)]
    protected RteHandler $rteHandler,
    #[Autowire(Utils::class)]
    protected Utils $utils,
    #[Autowire(LoggerInterface::class)]
    protected LoggerInterface $logger,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    parent::configure();
    $this
      ->addArgument(
        'id',
        InputArgument::REQUIRED,
        'Runtime environment ID',
        // @todo Suggested values.
      );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    try {
      $this->validate($input, $output);
    }
    catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());

      return self::FAILURE;
    }

    $gitHookName = NULL;
    $list = $this->rteHandler->getList();
    $rteId = $input->getArgument('id');
    $rte = $list[$rteId];

    $event = $this->eventDispatcher->dispatch(
      new RteCommandEvent(
        $input,
        $output,
        $gitHookName,
        $this->collectionBuilder(),
        [],
        $rte,
      ),
      RteCommandEvent::EVENT_SWITCH_TASKS_COLLECT,
    );

    $event = $this->eventDispatcher->dispatch(
      new RteCommandEvent(
        $event->input,
        $event->output,
        $gitHookName,
        $event->collectionBuilder,
        $event->taskDefinitions,
        $rte,
      ),
      RteCommandEvent::EVENT_SWITCH_TASKS_ALTER,
    );

    return $this->mtdRun(
      self::NAME,
      $event->collectionBuilder,
      $event->taskDefinitions,
    );
  }

  protected function validate(InputInterface $input, OutputInterface $output): void {
    $list = $this->rteHandler->getList();
    $rteId = $input->getArgument('id');

    if (!isset($list[$rteId])) {
      throw new \InvalidArgumentException("Runtime environment with ID '{$rteId}' does not exist.");
    }
  }

}

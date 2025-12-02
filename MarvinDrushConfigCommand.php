<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Drupal\marvin\ContainerInitializer;
use Drush\Attributes\Bootstrap as CliBootstrap;
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

#[AsCommand(
  name: self::NAME,
  description: 'Prints out the current Drush configuration.',
)]
#[CliFormatter(
  returnType: 'array',
  defaultFormatter: 'yaml',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
final class MarvinDrushConfigCommand extends Command {

  use AutowireTrait {
    create as protected autowireCreate;
  }
  use FormatterTrait;

  public const string NAME = 'marvin:drush-config';

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
  ) {
    parent::__construct();
  }

  public function execute(InputInterface $input, OutputInterface $output): int {
    $this->writeFormattedOutput($input, $output, $this->drushConfig->export());

    return self::SUCCESS;
  }

}

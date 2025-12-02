<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Drupal\marvin\ContainerInitializer;
use Drupal\marvin\VersionNumber\StructuredData;
use Drush\Attributes\Bootstrap as CliBootstrap;
use Drush\Attributes\Formatter as CliFormatter;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\AutowireTrait;
use Drush\Formatters\DrushFormatterManager;
use Drush\Formatters\FormatterTrait;
use Psr\Container\ContainerInterface;
use Sweetchuck\Utils\VersionNumber;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
  name: self::NAME,
  description: 'Prints out the current Drush configuration.',
)]
#[CliFormatter(
  returnType: StructuredData::class,
  defaultFormatter: 'string',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
final class MarvinVersionNumberBumpCommand extends Command {

  use AutowireTrait {
    create as protected autowireCreate;
  }
  use FormatterTrait;

  public const string NAME = 'marvin:version-number:bump';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    ContainerInitializer::initialize($container);

    return self::autowireCreate($container);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    #[Autowire('formatterManager')]
    protected DrushFormatterManager $formatterManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $versionNumberPartNames = VersionNumber::getPartNames();
    unset($versionNumberPartNames[array_search('metadata', $versionNumberPartNames)]);
    $stabilityNames = [
      'dev',
      'alpha',
      'beta',
      'rc',
    ];

    parent::configure();
    $this
      ->addArgument(
        'versionNumber',
        InputArgument::REQUIRED,
        'The original version number',
      )
      ->addArgument(
        'partName',
        InputArgument::REQUIRED,
        sprintf(
          'The part name of the version number fragment to increment. Allowed values: %s',
          implode(', ', $versionNumberPartNames),
        ),
        NULL,
        $versionNumberPartNames,
      )
      ->addOption(
        'preRelease',
        NULL,
        InputOption::VALUE_REQUIRED,
        '@todo',
        NULL,
        $stabilityNames,
      )
      ->addOption(
        'metadata',
        NULL,
        InputOption::VALUE_REQUIRED,
        'The metadata to append to the version number. Special value: "__inherit__"',
        FALSE,
      );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $partName = $input->getArgument('partName');
    $preRelease = $input->getOption('preRelease');
    if ($partName === 'preRelease' && $preRelease) {
      throw new \LogicException('--preRelease option can not be used when the "partName" argument is "preRelease"');
    }

    $versionNumber = VersionNumber::createFromString($input->getArgument('versionNumber'));
    $oldMetadata = $versionNumber->metadata;
    $versionNumber->bump($partName);

    if ($preRelease) {
      $versionNumber->preRelease = $preRelease;
    }

    $metadata = $input->getOption('metadata');
    if ($metadata === '__inherit__') {
      $metadata = $oldMetadata;
    }

    if ($metadata) {
      $versionNumber->metadata = $metadata;
    }

    $this->writeFormattedOutput($input, $output, new StructuredData($versionNumber));

    return self::SUCCESS;
  }

}

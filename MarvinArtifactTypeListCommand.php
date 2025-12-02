<?php

declare(strict_types=1);

namespace Drush\Commands\marvin;

use Drupal\marvin\ArtifactType\ArtifactTypeList;
use Drupal\marvin\ArtifactType\ArtifactTypeListEvent;
use Drupal\marvin\ContainerInitializer;
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
use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
  name: self::NAME,
  description: 'Lists all available artifact types.',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
#[CliFormatter(
  returnType: ArtifactTypeList::class,
  defaultFormatter: 'table',
)]
#[CliDefaultFields(
  fields: [
    'id',
    'label',
    'description',
  ],
)]
#[CliFieldLabels(
  labels: [
    'id' => 'ID',
    'label' => 'Label',
    'description' => 'Description',
    'weight' => 'Weight',
  ],
)]
final class MarvinArtifactTypeListCommand extends Command {

  use AutowireTrait {
    create as protected autowireCreate;
  }
  use FormatterTrait;

  public const string NAME = 'marvin:artifact-type';

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
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $artifactTypeList = new ArtifactTypeList();
    $comparer = $this->getArtifactTypeComparer();

    /** @noinspection PhpExpressionResultUnusedInspection */
    $this->eventDispatcher->dispatch(
      new ArtifactTypeListEvent($artifactTypeList),
      ArtifactTypeListEvent::EVENT_COLLECT,
    );
    $artifactTypeList->populateDefaultValues();
    $artifactTypeList->uasort($comparer);

    $event = $this->eventDispatcher->dispatch(
      new ArtifactTypeListEvent($artifactTypeList),
      ArtifactTypeListEvent::EVENT_ALTER,
    );
    $artifactTypeList->populateDefaultValues();
    $artifactTypeList->uasort($comparer);

    $this->formatterManager->write(
      $output,
      $input->getOption('format'),
      $event->artifactTypeList,
      $this
        ->getFormatterOptions()
        ->setInput($input)
        ->setOptions($input->getOptions()),
    );

    return self::SUCCESS;
  }

  protected function getArtifactTypeComparer(): callable {
    $comparer = new ArrayValueComparer();
    $comparer->setOptions($this->getArtifactTypesComparerOptions());

    return $comparer;
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  protected function getArtifactTypesComparerOptions(): array {
    return [
      'weight' => [
        'default' => 0,
      ],
      'label' => [
        'default' => '',
      ],
      'id' => [
        'default' => '',
      ],
    ];
  }

}

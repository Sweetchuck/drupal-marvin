<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit\Robo\Task;

use Drupal\marvin\Utils as MarvinUtils;
use Drupal\Tests\marvin\Helper\DummyOutput;
use Drupal\Tests\marvin\Helper\TaskBuilder;
use Drush\Config\DrushConfig;
use Drush\Drush;
use Drush\Log\Logger as DrushLogger;
use League\Container\Container as LeagueContainer;
use PHPUnit\Framework\TestCase;
use Robo\Collection\CollectionBuilder;
use Robo\Robo;
use Symfony\Component\Console\Application as SymfonyApplication;

class TaskTestBase extends TestCase {

  /**
   * @var \League\Container\ContainerInterface
   */
  protected $container;

  /**
   * @var \Drush\Config\DrushConfig
   */
  protected $config;

  /**
   * @var \Robo\Collection\CollectionBuilder
   */
  protected $builder;

  /**
   * @var \Drupal\Tests\marvin\Helper\TaskBuilder
   */
  protected $taskBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    Robo::unsetContainer();
    Drush::unsetContainer();

    $this->container = new LeagueContainer();
    $application = new SymfonyApplication('MarvinIncubator - DrushUnit', '1.0.0');
    $this->config = (new DrushConfig())
      ->set('drush.vendor-dir', '.');
    $input = NULL;
    $outputConfig = [];
    $output = new DummyOutput($outputConfig);

    $this->container->add('container', $this->container);
    $this->container->add('marvin.utils', MarvinUtils::class);
    $this->container->add('logger', DrushLogger::class);

    Robo::configureContainer($this->container, $application, $this->config, $input, $output);
    Drush::setContainer($this->container);

    $this->builder = CollectionBuilder::create($this->container, NULL);
    $this->taskBuilder = new TaskBuilder();
    $this->taskBuilder->setContainer($this->container);
    $this->taskBuilder->setBuilder($this->builder);
  }

}

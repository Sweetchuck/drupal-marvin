<?php

declare(strict_types=1);

namespace Drupal\marvin;

use Drupal\marvin\RuntimeEnvironment\Handler as RteHandler;
use Psr\Container\ContainerInterface;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\Utils\FileSystemUtils;
use Sweetchuck\Utils\StringUtils;
use Sweetchuck\Utils\Walker\FileSystemExistsWalker;
use Symfony\Component\Filesystem\Filesystem;

class ContainerInitializer extends ContainerInitializerBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function getServiceInfoList(): array {
    $definitions = [
      // Third-party - symfony/filesystem.
      Filesystem::class => [
        'class' => Filesystem::class,
        'shared' => TRUE,
      ],
      // Third-party - sweetchuck/utils.
      StringUtils::class => [
        'class' => StringUtils::class,
        'shared' => TRUE,
      ],
      FileSystemUtils::class => [
        'class' => FileSystemUtils::class,
        'shared' => TRUE,
      ],
      FileSystemExistsWalker::class => [
        'class' => FileSystemExistsWalker::class,
        'shared' => FALSE,
        'arguments' => [
          '@' . Filesystem::class,
        ],
      ],
      // Self.
      ProcessFactoryInterface::class => [
        'class' => ProcessFactory::class,
        'shared' => TRUE,
      ],
      FinderBuilder::class => [
        'class' => FinderBuilder::class,
        'shared' => TRUE,
      ],
      Utils::class => [
        'class' => Utils::class,
        'shared' => TRUE,
        'arguments' => [
          '@' . FileSystemUtils::class,
        ],
      ],
      RteHandler::class => [
        'class' => RteHandler::class,
        'shared' => TRUE,
        'arguments' => [
          '@eventDispatcher',
        ],
      ],
    ];

    foreach (BaseReporter::getServices() as $class) {
      $definitions[$class] = [
        'class' => $class,
        'shared' => FALSE,
      ];
    }

    return $definitions;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\marvin\RuntimeEnvironment;

use Drush\Commands\AutowireTrait;
use Sweetchuck\Utils\Comparer\ArrayValueComparer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Handler {

  use AutowireTrait;

  protected ?RuntimeEnvironmentList $list = NULL;

  protected ?string $currentId = NULL;

  public function __construct(
    #[Autowire('eventDispatcher')]
    protected EventDispatcherInterface $eventDispatcher,
  ) {}

  public function reset(): static {
    $this->list = NULL;
    $this->currentId = NULL;

    return $this;
  }

  public function getList(): RuntimeEnvironmentList {
    $this->initList();

    return $this->list;
  }

  protected function initList(): static {
    if ($this->list === NULL) {
      $this->list = new RuntimeEnvironmentList();
      $comparer = $this->getComparer();

      /* @noinspection PhpExpressionResultUnusedInspection */
      $this->eventDispatcher->dispatch(
        new RuntimeEnvironmentEvent($this->list),
        RuntimeEnvironmentEvent::EVENT_COLLECT,
      );
      $this->list->populateDefaultValues();
      $this->list->uasort($comparer);

      /* @noinspection PhpExpressionResultUnusedInspection */
      $this->eventDispatcher->dispatch(
        new RuntimeEnvironmentEvent($this->list),
        RuntimeEnvironmentEvent::EVENT_ALTER,
      );
      $this->list->populateDefaultValues();
      $this->list->uasort($comparer);
    }

    return $this;
  }

  public function initCurrent(): static {
    if ($this->currentId === NULL) {
      $this->initList();

      $event = $this->eventDispatcher->dispatch(
        new DetectCurrentEvent($this->list),
        DetectCurrentEvent::EVENT_DETECT_CURRENT,
      );

      $this->currentId = $event->currentId;
    }

    return $this;
  }

  public function getCurrentId(): ?string {
    $this->initCurrent();

    return $this->currentId;
  }

  /**
   * @phpstan-return null|array<string, mixed>
   */
  public function getCurrent(): ?array {
    $this->initCurrent();

    return $this->list[$this->currentId] ?? NULL;
  }

  public function getComparer(): callable {
    $comparer = new ArrayValueComparer();
    $comparer->setOptions($this->getComparerOptions());

    return $comparer;
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function getComparerOptions(): array {
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

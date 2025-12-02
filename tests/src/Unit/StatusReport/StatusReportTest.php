<?php

declare(strict_types=1);

namespace Drupal\Tests\marvin\Unit\StatusReport;

use Drupal\marvin\RfcLogLevel;
use Drupal\marvin\StatusReport\StatusReport;
use Drupal\marvin\StatusReport\StatusReportEntry;
use Drupal\marvin\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Sweetchuck\Utils\FileSystemUtils;

#[Group('marvin')]
#[CoversClass(StatusReport::class)]
class StatusReportTest extends TestCase {

  protected function createInstance(): StatusReport {
    return new StatusReport(
      new Utils(
        new FileSystemUtils(),
      ),
    );
  }

  public function testAllInOne(): void {
    $expected = [
      'a' => [
        'id' => 'a',
        'title' => 'a-t',
        'value' => 'a-v',
        'description' => 'a-d',
        'severity' => 5,
        'severityName' => 'Notice',
      ],
      'b' => [
        'id' => 'b',
        'title' => 'b-t',
        'value' => 'b-v',
        'description' => 'b-d',
        'severity' => 4,
        'severityName' => 'Warning',
      ],
      'c' => [
        'id' => 'c',
        'title' => 'c-t',
        'value' => 'c-v',
        'description' => 'c-d',
        'severity' => 3,
        'severityName' => 'Error',
      ],
      'd' => [
        'id' => 'd',
        'title' => 'd-t',
        'value' => 'd-v',
        'description' => 'd-d',
        'severity' => 2,
        'severityName' => 'Critical',
      ],
    ];

    $entryA = StatusReportEntry::__set_state($expected['a']);
    $entryB = StatusReportEntry::__set_state($expected['b']);
    $entryC = StatusReportEntry::__set_state($expected['c']);
    $entryD = StatusReportEntry::__set_state($expected['d']);

    $statusReport = $this->createInstance();
    static::assertSame([], $statusReport->jsonSerialize());
    static::assertSame(NULL, $statusReport->getHighestSeverity());
    static::assertSame(0, $statusReport->count());

    $statusReport->addEntries([$entryA, $entryB, $entryC, $entryD]);
    /** @var \Drupal\marvin\StatusReport\StatusReportEntryInterface $entry */
    foreach ($statusReport as $entryId => $entry) {
      static::assertSame($expected[$entryId], $entry->jsonSerialize());
    }

    static::assertSame($expected, $statusReport->jsonSerialize());
    static::assertSame($expected, $statusReport->getOutputData());
    static::assertSame(4, $statusReport->count());
    static::assertSame(3, $statusReport->getExitCode());
    static::assertSame(2, $statusReport->getHighestSeverity());

    $statusReport->removeEntries('b');
    unset($expected['b']);
    static::assertSame($expected, $statusReport->jsonSerialize());
    static::assertSame(3, $statusReport->count());
    static::assertSame(3, $statusReport->getExitCode());
    static::assertSame(2, $statusReport->getHighestSeverity());

    $statusReport->removeEntries($entryC, 'd');
    unset($expected['c'], $expected['d']);
    static::assertSame($expected, $statusReport->jsonSerialize());
    static::assertSame(1, $statusReport->count());
    static::assertSame(0, $statusReport->getExitCode());
    static::assertSame(5, $statusReport->getHighestSeverity());

    $statusReport->removeAllEntries();
    static::assertSame([], $statusReport->jsonSerialize());
    static::assertSame(0, $statusReport->count());
    static::assertSame(0, $statusReport->getExitCode());
    static::assertSame(NULL, $statusReport->getHighestSeverity());
  }

  public function testConvertToRowsOfFields(): void {
    $statusReport = $this->createInstance();

    $expected = [];
    $actualRowsOfFields = $statusReport->convertToRowsOfFields();
    static::assertSame($expected, $actualRowsOfFields->getArrayCopy());

    $statusReport->addEntries([
      (new StatusReportEntry())
        ->setId('a')
        ->setSeverity(RfcLogLevel::ERROR)
        ->setTitle('a-title')
        ->setDescription('a-description')
        ->setValue('a-value'),
      (new StatusReportEntry())
        ->setId('b')
        ->setSeverity(RfcLogLevel::WARNING)
        ->setTitle('b-title')
        ->setDescription('b-description')
        ->setValue('b-value'),
    ]);
    $expected = [
      'a' => [
        'id' => 'a',
        'title' => '<fg=red>a-title</>',
        'value' => 'a-value',
        'description' => 'a-description',
        'severity' => '<fg=red>3</>',
        'severityName' => '<fg=red>Error</>',
      ],
      'b' => [
        'id' => 'b',
        'title' => '<fg=yellow>b-title</>',
        'value' => 'b-value',
        'description' => 'b-description',
        'severity' => '<fg=yellow>4</>',
        'severityName' => '<fg=yellow>Warning</>',
      ],
    ];
    $actualRowsOfFields = $statusReport->convertToRowsOfFields();
    static::assertSame($expected, $actualRowsOfFields->getArrayCopy());
  }

}

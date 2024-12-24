<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ImportExport\Strategy\Import;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Cicada\Core\Content\ImportExport\Event\ImportExportAfterImportRecordEvent;
use Cicada\Core\Content\ImportExport\Event\ImportExportExceptionImportRecordEvent;
use Cicada\Core\Content\ImportExport\Strategy\Import\BatchImportStrategy;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\ImportExport\Struct\Progress;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\Event\NestedEventCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(BatchImportStrategy::class)]
class BatchImportStrategyTest extends ImportStrategyTestCase
{
    private BatchImportStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new BatchImportStrategy(
            $this->eventDispatcher,
            $this->repository
        );
    }

    public function testImport(): void
    {
        $context = Context::createDefaultContext();
        $progress = new Progress('logId', Progress::STATE_PROGRESS);
        $config = new Config([], [], []);

        $result = $this->strategy->import(['some' => 'data'], [], $config, $progress, $context);

        static::assertEquals([], $result->results);
        static::assertEquals([], $result->failedRecords);
    }

    #[DataProvider('importProvider')]
    public function testSuccessfulCommit(Config $config, string $method): void
    {
        $context = Context::createDefaultContext();
        $progress = new Progress('logId', Progress::STATE_PROGRESS);

        $this->strategy->import(['some' => 'data'], [], $config, $progress, $context);
        $this->strategy->import(['some' => 'data'], [], $config, $progress, $context);

        $writeResult = new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection(), []);

        $this->repository->expects(static::once())->method($method)->willReturn($writeResult);

        $this->eventDispatcher->expects(static::exactly(2))->method('dispatch');

        $progress = new Progress('logId', Progress::STATE_PROGRESS);

        $result = $this->strategy->commit($config, $progress, $context);

        static::assertEquals([$writeResult], $result->results);
        static::assertEquals([], $result->failedRecords);
        static::assertEquals(2, $progress->getProcessedRecords());
    }

    public function testFailedCommit(): void
    {
        $config = new Config(
            mapping: [],
            parameters: [
                'createEntities' => true,
                'updateEntities' => false,
            ],
            updateBy: []
        );

        $context = Context::createDefaultContext();
        $progress = new Progress('logId', Progress::STATE_PROGRESS);

        $this->strategy->import(['some' => 'data'], [], $config, $progress, $context);
        $this->strategy->import(['some' => 'data'], [], $config, $progress, $context);

        $writeResult = new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection(), []);

        $this->repository->expects(static::exactly(3))->method('create')->willReturnCallback(
            function () use ($writeResult) {
                static $counter = 0;
                if ($counter++ < 2) {
                    throw new \Exception('Error');
                }

                return $writeResult;
            }
        );

        $this->eventDispatcher->expects(static::exactly(2))
            ->method('dispatch')
            ->with(static::logicalOr(
                static::isInstanceOf(ImportExportAfterImportRecordEvent::class),
                static::isInstanceOf(ImportExportExceptionImportRecordEvent::class)
            ));

        $result = $this->strategy->commit($config, $progress, $context);

        static::assertEquals([$writeResult], $result->results);
        static::assertEquals([
            ['some' => 'data', '_error' => 'Error'],
        ], $result->failedRecords);
    }
}

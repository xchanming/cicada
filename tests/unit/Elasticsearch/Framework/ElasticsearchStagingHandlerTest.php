<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Framework;

use Cicada\Core\Framework\Context;
use Cicada\Core\Maintenance\Staging\Event\SetupStagingEvent;
use Cicada\Elasticsearch\Framework\ElasticsearchHelper;
use Cicada\Elasticsearch\Framework\ElasticsearchOutdatedIndexDetector;
use Cicada\Elasticsearch\Framework\ElasticsearchStagingHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[CoversClass(ElasticsearchStagingHandler::class)]
class ElasticsearchStagingHandlerTest extends TestCase
{
    public function testCancel(): void
    {
        $event = new SetupStagingEvent(Context::createDefaultContext(), $this->createMock(SymfonyStyle::class));

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper->method('allowIndexing')->willReturn(true);

        $detector = $this->createMock(ElasticsearchOutdatedIndexDetector::class);
        $detector->method('getAllUsedIndices')->willReturn(['index1', 'index2']);

        $handler = new ElasticsearchStagingHandler(true, $helper, $detector);
        $handler($event);

        static::assertTrue($event->canceled);
    }

    #[DataProvider('disabledProvider')]
    public function testDisabled(bool $check, bool $indexing): void
    {
        $event = new SetupStagingEvent(Context::createDefaultContext(), $this->createMock(SymfonyStyle::class));

        $helper = $this->createMock(ElasticsearchHelper::class);
        $helper->method('allowIndexing')->willReturn($indexing);

        $detector = $this->createMock(ElasticsearchOutdatedIndexDetector::class);
        $detector
            ->expects(static::never())
            ->method('getAllUsedIndices');
        $handler = new ElasticsearchStagingHandler($check, $helper, $detector);

        $handler($event);

        static::assertFalse($event->canceled);
    }

    public static function disabledProvider(): \Generator
    {
        yield 'check disabled, but indexing enabled' => [false, true];
        yield 'check enabled, but indexing disabled' => [true, false];
        yield 'both disabled' => [false, false];
    }
}

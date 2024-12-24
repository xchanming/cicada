<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Cleanup;

use Cicada\Core\Content\Media\UnusedMediaPurger;
use Cicada\Core\Content\Product\Aggregate\ProductDownload\ProductDownloadDefinition;
use Cicada\Core\Content\Product\Cleanup\CleanupUnusedDownloadMediaTaskHandler;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(CleanupUnusedDownloadMediaTaskHandler::class)]
class CleanupUnusedDownloadMediaTaskHandlerTest extends TestCase
{
    private MockObject&UnusedMediaPurger $purger;

    private CleanupUnusedDownloadMediaTaskHandler $handler;

    protected function setUp(): void
    {
        $this->purger = $this->createMock(UnusedMediaPurger::class);

        $this->handler = new CleanupUnusedDownloadMediaTaskHandler(
            $this->createMock(EntityRepository::class),
            $this->createMock(LoggerInterface::class),
            $this->purger
        );
    }

    public function testRun(): void
    {
        $this->purger
            ->expects(static::once())
            ->method('deleteNotUsedMedia')
            ->with(null, null, null, ProductDownloadDefinition::ENTITY_NAME);

        $this->handler->run();
    }
}

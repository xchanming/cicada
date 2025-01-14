<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Cleanup;

use Cicada\Core\Content\Product\Cleanup\CleanupUnusedDownloadMediaTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CleanupUnusedDownloadMediaTask::class)]
class CleanupUnusedDownloadMediaTaskTest extends TestCase
{
    public function testGetTaskName(): void
    {
        static::assertEquals('product_download.media.cleanup', CleanupUnusedDownloadMediaTask::getTaskName());
    }

    public function testGetDefaultInterval(): void
    {
        static::assertEquals(2628000, CleanupUnusedDownloadMediaTask::getDefaultInterval());
    }
}

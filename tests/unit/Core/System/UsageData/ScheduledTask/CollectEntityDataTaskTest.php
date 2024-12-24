<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\UsageData\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\UsageData\ScheduledTask\CollectEntityDataTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('data-services')]
#[CoversClass(CollectEntityDataTask::class)]
class CollectEntityDataTaskTest extends TestCase
{
    public function testItHandlesCorrectTask(): void
    {
        static::assertEquals('usage_data.entity_data.collect', CollectEntityDataTask::getTaskName());
    }

    public function testItIsRescheduledEvery24Hours(): void
    {
        static::assertEquals(60 * 60 * 24, CollectEntityDataTask::getDefaultInterval());
    }
}

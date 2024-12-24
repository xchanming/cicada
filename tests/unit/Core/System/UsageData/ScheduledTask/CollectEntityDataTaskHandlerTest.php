<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\UsageData\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Cicada\Core\System\UsageData\ScheduledTask\CollectEntityDataTaskHandler;
use Cicada\Core\System\UsageData\Services\EntityDispatchService;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[Package('data-services')]
#[CoversClass(CollectEntityDataTaskHandler::class)]
class CollectEntityDataTaskHandlerTest extends TestCase
{
    public function testItStartsCollectingData(): void
    {
        $entityDispatchService = $this->createMock(EntityDispatchService::class);
        $entityDispatchService->expects(static::once())
            ->method('dispatchCollectEntityDataMessage');

        $taskHandler = new CollectEntityDataTaskHandler(
            new StaticEntityRepository([], new ScheduledTaskDefinition()),
            $this->createMock(LoggerInterface::class),
            $entityDispatchService
        );

        $taskHandler->run();
    }
}

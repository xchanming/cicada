<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\UsageData\EntitySync;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\System\UsageData\EntitySync\CollectEntityDataMessage;
use Cicada\Core\System\UsageData\EntitySync\CollectEntityDataMessageHandler;
use Cicada\Core\System\UsageData\Services\EntityDispatchService;

/**
 * @internal
 */
#[CoversClass(CollectEntityDataMessageHandler::class)]
class CollectEntityDataMessageHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $entityDispatchService = $this->createMock(EntityDispatchService::class);
        $entityDispatchService->expects(static::once())
            ->method('dispatchIterateEntityMessages');

        $messageHandler = new CollectEntityDataMessageHandler($entityDispatchService);
        $messageHandler(new CollectEntityDataMessage());
    }
}

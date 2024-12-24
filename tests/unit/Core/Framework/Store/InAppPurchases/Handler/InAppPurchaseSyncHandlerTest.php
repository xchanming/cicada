<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\InAppPurchases\Handler;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\InAppPurchase\Handler\InAppPurchaseUpdateHandler;
use Cicada\Core\Framework\Store\InAppPurchase\Services\InAppPurchaseUpdater;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InAppPurchaseUpdateHandler::class)]
class InAppPurchaseSyncHandlerTest extends TestCase
{
    public function testRunWithActiveInAppPurchases(): void
    {
        $syncService = $this->createMock(InAppPurchaseUpdater::class);
        $syncService->expects(static::once())
            ->method('update')
            ->with(Context::createCLIContext());

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::never())
            ->method('error');

        $handler = new InAppPurchaseUpdateHandler(
            $this->createMock(EntityRepository::class),
            $logger,
            $syncService
        );

        $handler->run();
    }
}

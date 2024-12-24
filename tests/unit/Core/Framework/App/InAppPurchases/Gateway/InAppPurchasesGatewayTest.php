<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\InAppPurchases\Gateway;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\InAppPurchases\Event\InAppPurchasesGatewayEvent;
use Cicada\Core\Framework\App\InAppPurchases\Gateway\InAppPurchasesGateway;
use Cicada\Core\Framework\App\InAppPurchases\Payload\InAppPurchasesPayload;
use Cicada\Core\Framework\App\InAppPurchases\Payload\InAppPurchasesPayloadService;
use Cicada\Core\Framework\App\InAppPurchases\Response\InAppPurchasesResponse;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(InAppPurchasesGateway::class)]
#[Package('checkout')]
class InAppPurchasesGatewayTest extends TestCase
{
    public function testProcess(): void
    {
        $context = Context::createDefaultContext();

        $app = new AppEntity();
        $app->setId(Uuid::randomHex());
        $app->setUniqueIdentifier(Uuid::randomHex());
        $app->setInAppPurchasesGatewayUrl('https://example.com/filter');

        $inAppPurchasesPayload = new InAppPurchasesPayload(['purchase-1', 'purchase-2']);

        $inAppPurchaseFilterResponse = new InAppPurchasesResponse();
        $inAppPurchaseFilterResponse->purchases = [
            'purchase-1',
            'purchase-2',
        ];

        $payloadService = $this->createMock(InAppPurchasesPayloadService::class);
        $payloadService
            ->expects(static::once())
            ->method('request')
            ->with(
                'https://example.com/filter',
                $inAppPurchasesPayload,
                $app
            )
            ->willReturn($inAppPurchaseFilterResponse);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::equalTo(new InAppPurchasesGatewayEvent($inAppPurchaseFilterResponse)));

        $gateway = new InAppPurchasesGateway($payloadService, $eventDispatcher);
        $response = $gateway->process($inAppPurchasesPayload, $context, $app);

        static::assertSame($inAppPurchaseFilterResponse, $response);
        static::assertCount(2, $response->purchases);
    }
}

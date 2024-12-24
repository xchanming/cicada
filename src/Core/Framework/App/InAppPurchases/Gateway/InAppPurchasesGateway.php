<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\InAppPurchases\Gateway;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\InAppPurchases\Event\InAppPurchasesGatewayEvent;
use Cicada\Core\Framework\App\InAppPurchases\Payload\InAppPurchasesPayload;
use Cicada\Core\Framework\App\InAppPurchases\Payload\InAppPurchasesPayloadService;
use Cicada\Core\Framework\App\InAppPurchases\Response\InAppPurchasesResponse;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class InAppPurchasesGateway
{
    public function __construct(
        readonly private InAppPurchasesPayloadService $payloadService,
        readonly private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(InAppPurchasesPayload $payload, Context $context, AppEntity $app): ?InAppPurchasesResponse
    {
        $url = $app->getInAppPurchasesGatewayUrl();

        if ($url === null) {
            return null;
        }

        $response = $this->payloadService->request($url, $payload, $app, $context);

        $this->eventDispatcher->dispatch(new InAppPurchasesGatewayEvent($response));

        return $response;
    }
}

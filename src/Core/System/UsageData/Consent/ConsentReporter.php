<?php declare(strict_types=1);

namespace Cicada\Core\System\UsageData\Consent;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\InstanceService;
use Cicada\Core\Framework\Store\Services\StoreService;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\System\UsageData\Services\ShopIdProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
#[Package('data-services')]
class ConsentReporter implements EventSubscriberInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ShopIdProvider $shopIdProvider,
        private readonly SystemConfigService $systemConfigService,
        private readonly InstanceService $instanceService,
        private readonly string $appUrl,
        private readonly bool $dispatchEnabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsentStateChangedEvent::class => 'reportConsent',
        ];
    }

    public function reportConsent(ConsentStateChangedEvent $event): void
    {
        if (!$this->dispatchEnabled) {
            return;
        }

        $payload = [
            'app_url' => $this->appUrl,
            'consent_state' => $event->getState()->value,
            'license_host' => $this->systemConfigService->getString(StoreService::CONFIG_KEY_STORE_LICENSE_DOMAIN),
            'shop_id' => $this->shopIdProvider->getShopId(),
            'cicada_version' => $this->instanceService->getCicadaVersion(),
        ];

        try {
            $this->client->request(
                Request::METHOD_POST,
                '/v1/consent',
                [
                    'headers' => [
                        'Cicada-Shop-Id' => $this->shopIdProvider->getShopId(),
                    ],
                    'body' => json_encode($payload, \JSON_THROW_ON_ERROR),
                ]
            );
        } catch (\Throwable) {
        }
    }
}

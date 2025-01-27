<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\ShopId;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal only for use by the app-system
 *
 * @phpstan-type ShopId array{value: string, app_url: ?string}
 */
#[Package('framework')]
class ShopIdProvider
{
    final public const SHOP_ID_SYSTEM_CONFIG_KEY = 'core.app.shopId';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ActiveAppsLoader $activeAppsLoader
    ) {
    }

    /**
     * @throws AppUrlChangeDetectedException
     */
    public function getShopId(): string
    {
        $shopId = $this->systemConfigService->get(self::SHOP_ID_SYSTEM_CONFIG_KEY);

        if (!\is_array($shopId)) {
            $newShopId = $this->generateShopId();
            $this->setShopId($newShopId, (string) EnvironmentHelper::getVariable('APP_URL'));

            return $newShopId;
        }

        $appUrl = EnvironmentHelper::getVariable('APP_URL');
        if (\is_string($appUrl) && $appUrl !== ($shopId['app_url'] ?? '')) {
            if ($this->activeAppsLoader->getActiveApps()) {
                throw new AppUrlChangeDetectedException($shopId['app_url'], $appUrl, $shopId['value']);
            }

            // if the shop does not have any apps we can update the existing shop id value
            // with the new APP_URL as no app knows the shop id
            $this->setShopId($shopId['value'], $appUrl);
        }

        return $shopId['value'];
    }

    public function setShopId(string $shopId, string $appUrl): void
    {
        /** @var ShopId|null $oldShopId */
        $oldShopId = $this->systemConfigService->get(self::SHOP_ID_SYSTEM_CONFIG_KEY);
        $newShopId = [
            'app_url' => $appUrl,
            'value' => $shopId,
        ];

        $this->systemConfigService->set(self::SHOP_ID_SYSTEM_CONFIG_KEY, $newShopId);

        $this->eventDispatcher->dispatch(new ShopIdChangedEvent($newShopId, $oldShopId));
    }

    public function deleteShopId(): void
    {
        $this->systemConfigService->delete(self::SHOP_ID_SYSTEM_CONFIG_KEY);

        $this->eventDispatcher->dispatch(new ShopIdDeletedEvent());
    }

    private function generateShopId(): string
    {
        return Random::getAlphanumericString(16);
    }
}

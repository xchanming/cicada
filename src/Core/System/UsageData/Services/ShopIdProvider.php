<?php declare(strict_types=1);

namespace Cicada\Core\System\UsageData\Services;

use Cicada\Core\Framework\App\ShopId\ShopIdProvider as AppSystemShopIdProvider;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SystemConfig\SystemConfigService;

/**
 * @internal
 */
#[Package('data-services')]
class ShopIdProvider
{
    public function __construct(
        private readonly AppSystemShopIdProvider $shopIdProvider,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function getShopId(): string
    {
        $shopId = $this->systemConfigService->get(AppSystemShopIdProvider::SHOP_ID_SYSTEM_CONFIG_KEY);

        if (!\is_array($shopId)) {
            return $this->shopIdProvider->getShopId();
        }

        return $shopId['value'];
    }
}

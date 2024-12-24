<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Update\Checkers;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\StoreClient;
use Cicada\Core\Framework\Update\Struct\ValidationResult;
use Cicada\Core\System\SystemConfig\SystemConfigService;

#[Package('services-settings')]
class LicenseCheck
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly StoreClient $storeClient
    ) {
    }

    public function check(): ValidationResult
    {
        $licenseHost = $this->systemConfigService->get('core.store.licenseHost');

        if (empty($licenseHost) || $this->storeClient->isShopUpgradeable()) {
            return new ValidationResult('validCicadaLicense', true, 'validCicadaLicense');
        }

        return new ValidationResult('invalidCicadaLicense', false, 'invalidCicadaLicense');
    }
}

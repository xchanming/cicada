<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;

#[Package('services-settings')]
class BundleConfigNotFoundException extends CicadaHttpException
{
    public function __construct(
        string $configPath,
        string $bundleName
    ) {
        parent::__construct(
            'Could not find "{{ configPath }}" for bundle "{{ bundle }}".',
            [
                'configPath' => $configPath,
                'bundle' => $bundleName,
            ]
        );
    }

    public function getErrorCode(): string
    {
        return 'SYSTEM__BUNDLE_CONFIG_NOT_FOUND';
    }
}

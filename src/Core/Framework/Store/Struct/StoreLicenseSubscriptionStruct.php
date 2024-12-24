<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @codeCoverageIgnore
 */
#[Package('checkout')]
class StoreLicenseSubscriptionStruct extends Struct
{
    /**
     * @var \DateTimeInterface
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $expirationDate;

    public function getApiAlias(): string
    {
        return 'store_license_subscription';
    }
}

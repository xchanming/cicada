<?php declare(strict_types=1);

namespace Cicada\Core\Service;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
readonly class ServiceRegistryEntry
{
    public function __construct(public string $name, public string $description, public string $host, public string $appEndpoint, public bool $activateOnInstall = true, public ?string $licenseSyncEndPoint = null)
    {
    }
}

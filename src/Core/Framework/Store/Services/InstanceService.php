<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class InstanceService
{
    public function __construct(
        private readonly string $cicadaVersion,
        private readonly ?string $instanceId
    ) {
    }

    public function getCicadaVersion(): string
    {
        if (str_ends_with($this->cicadaVersion, '-dev')) {
            return '___VERSION___';
        }

        return $this->cicadaVersion;
    }

    public function getInstanceId(): ?string
    {
        return $this->instanceId;
    }
}

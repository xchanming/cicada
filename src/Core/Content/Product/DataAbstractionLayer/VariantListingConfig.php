<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\DataAbstractionLayer;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('core')]
class VariantListingConfig extends Struct
{
    /**
     * @param array<string, string>|null $configuratorGroupConfig
     */
    public function __construct(
        protected ?bool $displayParent,
        protected ?string $mainVariantId,
        protected ?array $configuratorGroupConfig
    ) {
    }

    public function getDisplayParent(): ?bool
    {
        return $this->displayParent;
    }

    public function getMainVariantId(): ?string
    {
        return $this->mainVariantId;
    }

    /**
     * @return array<string, string>|null
     */
    public function getConfiguratorGroupConfig(): ?array
    {
        return $this->configuratorGroupConfig;
    }
}

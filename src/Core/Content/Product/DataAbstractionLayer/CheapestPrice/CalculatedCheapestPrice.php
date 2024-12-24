<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class CalculatedCheapestPrice extends CalculatedPrice
{
    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $hasRange = false;

    protected ?string $variantId = null;

    public function hasRange(): bool
    {
        return $this->hasRange;
    }

    public function setHasRange(bool $hasRange): void
    {
        $this->hasRange = $hasRange;
    }

    public function getApiAlias(): string
    {
        return 'calculated_cheapest_price';
    }

    public function setVariantId(string $variantId): void
    {
        $this->variantId = $variantId;
    }

    public function getVariantId(): ?string
    {
        return $this->variantId;
    }
}

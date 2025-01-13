<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItem;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('checkout')]
class QuantityInformation extends Struct
{
    /**
     * @var int
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $minPurchase = 1;

    /**
     * @var int|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $maxPurchase;

    /**
     * @var int|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $purchaseSteps = 1;

    public function getMinPurchase(): int
    {
        return $this->minPurchase;
    }

    public function setMinPurchase(int $minPurchase): QuantityInformation
    {
        if ($minPurchase < 1) {
            throw CartException::unexpectedValueException('minPurchase must be greater or equal 1');
        }

        $this->minPurchase = $minPurchase;

        return $this;
    }

    public function getMaxPurchase(): ?int
    {
        return $this->maxPurchase;
    }

    public function setMaxPurchase(int $maxPurchase): QuantityInformation
    {
        $this->maxPurchase = $maxPurchase;

        return $this;
    }

    public function getPurchaseSteps(): ?int
    {
        return $this->purchaseSteps;
    }

    public function setPurchaseSteps(int $purchaseSteps): QuantityInformation
    {
        if ($purchaseSteps < 1) {
            throw CartException::unexpectedValueException('purchaseSteps must be greater or equal 1');
        }

        $this->purchaseSteps = $purchaseSteps;

        return $this;
    }

    public function getApiAlias(): string
    {
        return 'cart_quantity_information';
    }
}

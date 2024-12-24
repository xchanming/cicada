<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Cart;

use Cicada\Core\Checkout\Promotion\PromotionEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class PromotionCodeTuple
{
    public function __construct(
        private readonly string $code,
        private readonly PromotionEntity $promotion
    ) {
    }

    /**
     * Gets the code of the tuple.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Gets the promotion for this code tuple.
     */
    public function getPromotion(): PromotionEntity
    {
        return $this->promotion;
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscountPrice;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<PromotionDiscountPriceEntity>
 */
#[Package('checkout')]
class PromotionDiscountPriceCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'promotion_discount_price_collection';
    }

    protected function getExpectedClass(): string
    {
        return PromotionDiscountPriceEntity::class;
    }
}

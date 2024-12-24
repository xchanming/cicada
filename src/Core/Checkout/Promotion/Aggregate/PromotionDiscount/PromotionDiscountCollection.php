<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Aggregate\PromotionDiscount;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<PromotionDiscountEntity>
 */
#[Package('buyers-experience')]
class PromotionDiscountCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'promotion_discount_collection';
    }

    protected function getExpectedClass(): string
    {
        return PromotionDiscountEntity::class;
    }
}

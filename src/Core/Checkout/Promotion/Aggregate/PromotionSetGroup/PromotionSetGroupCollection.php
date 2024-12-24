<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Aggregate\PromotionSetGroup;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<PromotionSetGroupEntity>
 */
#[Package('buyers-experience')]
class PromotionSetGroupCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'promotion_set_group_collection';
    }

    protected function getExpectedClass(): string
    {
        return PromotionSetGroupEntity::class;
    }
}

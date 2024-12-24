<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Gateway;

use Cicada\Core\Checkout\Promotion\PromotionCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('buyers-experience')]
interface PromotionGatewayInterface
{
    /**
     * Gets a list of promotions for the provided criteria and
     * sales channel context.
     *
     * @deprecated tag:v6.7.0 - reason:return-type-change - Return type will be changed to PromotionCollection
     *
     * @return PromotionCollection
     */
    public function get(Criteria $criteria, SalesChannelContext $context): EntityCollection;
}

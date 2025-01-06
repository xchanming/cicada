<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Gateway\Template;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('checkout')]
class PermittedIndividualCodePromotions extends MultiFilter
{
    /**
     * Gets a criteria for all permitted promotions of the provided
     * sales channel context, that do require an individual code
     * and have not yet been used in an order.
     *
     * @param list<string> $codes
     */
    public function __construct(
        array $codes,
        string $salesChannelId
    ) {
        $activeDateRange = new ActiveDateRange();

        parent::__construct(
            MultiFilter::CONNECTION_AND,
            [
                new EqualsFilter('active', true),
                new EqualsFilter('promotion.salesChannels.salesChannelId', $salesChannelId),
                $activeDateRange,
                new EqualsFilter('useCodes', true),
                new EqualsFilter('useIndividualCodes', true),
                new EqualsAnyFilter('promotion.individualCodes.code', $codes),
                // a payload of null means, they have not yet been redeemed
                new EqualsFilter('promotion.individualCodes.payload', null),
            ]
        );
    }
}

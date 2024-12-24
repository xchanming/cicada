<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('services-settings')]
class LineItemScope extends CheckoutRuleScope
{
    public function __construct(
        protected LineItem $lineItem,
        SalesChannelContext $context
    ) {
        parent::__construct($context);
    }

    public function getLineItem(): LineItem
    {
        return $this->lineItem;
    }
}

<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Rule\Helper;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Rule\LineItemPropertyRule;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class CartRuleScopeCase
{
    /**
     * @param LineItem[] $lineItems
     */
    public function __construct(
        public string $description,
        public bool $match,
        public LineItemPropertyRule $rule,
        public array $lineItems
    ) {
    }
}

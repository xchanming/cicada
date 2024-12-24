<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\TaxProvider;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\TaxProvider\Struct\TaxProviderResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
abstract class AbstractTaxProvider
{
    abstract public function provide(Cart $cart, SalesChannelContext $context): TaxProviderResult;
}

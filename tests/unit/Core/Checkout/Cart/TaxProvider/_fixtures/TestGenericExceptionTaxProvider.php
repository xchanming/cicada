<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\TaxProvider\_fixtures;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\TaxProvider\AbstractTaxProvider;
use Cicada\Core\Checkout\Cart\TaxProvider\Struct\TaxProviderResult;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
class TestGenericExceptionTaxProvider extends AbstractTaxProvider
{
    public function provide(Cart $cart, SalesChannelContext $context): TaxProviderResult
    {
        throw new \Exception('Test exception');
    }
}

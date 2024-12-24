<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Transaction;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Transaction\Struct\Transaction;
use Cicada\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class TransactionProcessor
{
    public function process(Cart $cart, SalesChannelContext $context): TransactionCollection
    {
        $price = $cart->getPrice()->getTotalPrice();

        return new TransactionCollection([
            new Transaction(
                new CalculatedPrice(
                    $price,
                    $price,
                    $cart->getPrice()->getCalculatedTaxes(),
                    $cart->getPrice()->getTaxRules()
                ),
                $context->getPaymentMethod()->getId()
            ),
        ]);
    }
}

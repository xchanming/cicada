<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Processor;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\CartProcessorInterface;
use Cicada\Core\Checkout\Cart\Error\IncompleteLineItemError;
use Cicada\Core\Checkout\Cart\LineItem\CartDataCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\CurrencyPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CurrencyPriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\FloatComparator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class DiscountCartProcessor implements CartProcessorInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PercentagePriceCalculator $percentageCalculator,
        private readonly CurrencyPriceCalculator $currencyCalculator
    ) {
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $items = $original->getLineItems()->filterType(LineItem::DISCOUNT_LINE_ITEM);

        $goods = $toCalculate->getLineItems()->filterGoods();

        foreach ($items as $item) {
            $definition = $item->getPriceDefinition();

            try {
                $price = $this->calculate($definition, $goods, $context);
            } catch (CartException) {
                $original->remove($item->getId());
                $toCalculate->addErrors(new IncompleteLineItemError($item->getId(), 'price'));

                continue;
            }

            if (!$this->validate($price, $goods, $toCalculate)) {
                $original->remove($item->getId());

                continue;
            }

            $item->setPrice($price);
            $item->setShippingCostAware(false);

            $toCalculate->add($item);
        }
    }

    private function validate(CalculatedPrice $price, LineItemCollection $goods, Cart $cart): bool
    {
        if ($goods->count() <= 0) {
            return false;
        }

        if (FloatComparator::greaterThan($price->getTotalPrice(), 0)) {
            return true;
        }

        if (FloatComparator::equals($price->getTotalPrice(), 0)) {
            return false;
        }

        // should not be possible to get negative carts
        $total = $price->getTotalPrice() + $cart->getLineItems()->getPrices()->sum()->getTotalPrice();

        return $total >= 0;
    }

    private function calculate(?PriceDefinitionInterface $definition, LineItemCollection $goods, SalesChannelContext $context): CalculatedPrice
    {
        if ($definition instanceof PercentagePriceDefinition) {
            return $this->percentageCalculator->calculate($definition->getPercentage(), $goods->getPrices(), $context);
        }

        if ($definition instanceof CurrencyPriceDefinition) {
            return $this->currencyCalculator->calculate($definition->getPrice(), $goods->getPrices(), $context);
        }

        throw CartException::invalidPriceDefinition();
    }
}

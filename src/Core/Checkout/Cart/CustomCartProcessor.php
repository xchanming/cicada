<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Cicada\Core\Checkout\Cart\LineItem\CartDataCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Content\Product\State;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class CustomCartProcessor implements CartProcessorInterface, CartDataCollectorInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly QuantityPriceCalculator $calculator)
    {
    }

    public function collect(
        CartDataCollection $data,
        Cart $original,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $lineItems = $original
            ->getLineItems()
            ->filterFlatByType(LineItem::CUSTOM_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            $this->enrich($lineItem);
        }
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $lineItems = $original->getLineItems()->filterType(LineItem::CUSTOM_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            $definition = $lineItem->getPriceDefinition();

            if (!$definition instanceof QuantityPriceDefinition) {
                continue;
            }

            $lineItem->setPrice(
                $this->calculator->calculate(
                    $definition,
                    $context
                )
            );

            $lineItem->setShippingCostAware(!$lineItem->hasState(State::IS_DOWNLOAD));

            $toCalculate->add($lineItem);
        }
    }

    private function enrich(LineItem $lineItem): void
    {
        if ($lineItem->getDeliveryInformation() !== null) {
            return;
        }

        $lineItem->setDeliveryInformation(new DeliveryInformation($lineItem->getQuantity(), 0, false));
    }
}

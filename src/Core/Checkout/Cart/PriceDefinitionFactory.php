<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InvalidPriceFieldTypeException;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class PriceDefinitionFactory
{
    public function factory(Context $context, array $priceDefinition, string $lineItemType): PriceDefinitionInterface
    {
        if (!isset($priceDefinition['type'])) {
            throw new InvalidPriceFieldTypeException('none');
        }

        return match ($priceDefinition['type']) {
            QuantityPriceDefinition::TYPE => QuantityPriceDefinition::fromArray($priceDefinition),
            AbsolutePriceDefinition::TYPE => new AbsolutePriceDefinition((float) $priceDefinition['price']),
            PercentagePriceDefinition::TYPE => new PercentagePriceDefinition($priceDefinition['percentage']),
            default => throw new InvalidPriceFieldTypeException($priceDefinition['type']),
        };
    }
}

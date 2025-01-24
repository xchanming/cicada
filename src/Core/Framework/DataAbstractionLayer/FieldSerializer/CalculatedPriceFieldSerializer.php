<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\ListPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\ReferencePrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
class CalculatedPriceFieldSerializer extends JsonFieldSerializer
{
    public function encode(
        Field $field,
        EntityExistence $existence,
        KeyValuePair $data,
        WriteParameterBag $parameters
    ): \Generator {
        $value = json_decode(json_encode($data->getValue(), \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR);

        unset($value['extensions']);
        if (isset($value['listPrice'])) {
            unset($value['listPrice']['extensions']);
        }

        $data->setValue($value);

        yield from parent::encode($field, $existence, $data, $parameters);
    }

    public function decode(Field $field, mixed $value): ?CalculatedPrice
    {
        if ($value === null) {
            return null;
        }

        $decoded = parent::decode($field, $value);
        if (!\is_array($decoded)) {
            return null;
        }

        $taxRules = array_map(
            fn (array $tax) => new TaxRule(
                (float) $tax['taxRate'],
                (float) $tax['percentage']
            ),
            $decoded['taxRules']
        );

        $calculatedTaxes = array_map(
            fn (array $tax) => new CalculatedTax(
                (float) $tax['tax'],
                (float) $tax['taxRate'],
                (float) $tax['price']
            ),
            $decoded['calculatedTaxes']
        );

        $referencePriceDefinition = null;
        if (isset($decoded['referencePrice'])) {
            $refPrice = $decoded['referencePrice'];

            $referencePriceDefinition = new ReferencePrice(
                $refPrice['price'],
                $refPrice['purchaseUnit'],
                $refPrice['referenceUnit'],
                $refPrice['unitName']
            );
        }

        $listPrice = null;
        if (isset($decoded['listPrice'])) {
            $listPrice = ListPrice::createFromUnitPrice(
                (float) $decoded['unitPrice'],
                (float) $decoded['listPrice']['price']
            );
        }

        return new CalculatedPrice(
            (float) $decoded['unitPrice'],
            (float) $decoded['totalPrice'],
            new CalculatedTaxCollection($calculatedTaxes),
            new TaxRuleCollection($taxRules),
            (int) $decoded['quantity'],
            $referencePriceDefinition,
            $listPrice
        );
    }
}

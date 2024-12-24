<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Facade;

use Doctrine\DBAL\Connection;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\PriceCollection as CalculatedPriceCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal PriceFacade is public api, this class is only a service layer for better testing and re-usability for internal logic
 */
#[Package('checkout')]
class ScriptPriceStubs implements ResetInterface
{
    /**
     * @var array<string, string>
     */
    private array $currencies = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly QuantityPriceCalculator $quantityCalculator,
        private readonly PercentagePriceCalculator $percentageCalculator
    ) {
    }

    public function calculateQuantity(QuantityPriceDefinition $definition, SalesChannelContext $context): CalculatedPrice
    {
        return $this->quantityCalculator->calculate($definition, $context);
    }

    public function calculatePercentage(float $percentage, CalculatedPriceCollection $prices, SalesChannelContext $context): CalculatedPrice
    {
        return $this->percentageCalculator->calculate($percentage, $prices, $context);
    }

    /**
     * // script value (only use case: shop owner defines a script)
     * set price = services.cart.price.create({
     *      'default': { gross: 100, net: 84.03},
     *      'USD': { gross: 59.5 net: 50 }
     * });
     *      => default will be validate on function call (shop owner has to define it)
     *      => we cannot calculate the net/gross equivalent value because we do not know how the price will be taxed
     *
     * // storage value (custom fields, product.price, etc)
     * set price = {
     *      { gross: 100, net: 50, currencyId: {currency-id} },
     *      { gross: 90, net: 40, currencyId: {currency-id} },
     * }; => default is validate when persisting as storage
     *
     * @param array<string, array{gross:float, net:float, linked?:bool}> $price
     */
    public function build(array $price): PriceCollection
    {
        $collection = new PriceCollection();

        $price = $this->validatePrice($price);

        foreach ($price as $id => $value) {
            $collection->add(
                new Price($id, $value['net'], $value['gross'], $value['linked'] ?? false)
            );
        }

        return $collection;
    }

    public function reset(): void
    {
        $this->currencies = [];
    }

    /**
     * @param array<string, array{gross:float, net:float, linked?:bool}> $price
     *
     * @return array<string, array{gross:float, net:float, linked?:bool}>
     */
    private function validatePrice(array $price): array
    {
        $price = $this->resolveIsoCodes($price);

        if (!\array_key_exists(Defaults::CURRENCY, $price)) {
            throw CartException::invalidPriceDefinition();
        }

        foreach ($price as $id => $value) {
            if (!Uuid::isValid($id)) {
                throw CartException::invalidPriceDefinition();
            }

            if (!\array_key_exists('gross', $value)) {
                throw CartException::invalidPriceDefinition();
            }

            if (!\array_key_exists('net', $value)) {
                throw CartException::invalidPriceDefinition();
            }
        }

        return $price;
    }

    /**
     * @param array<string, array{gross:float, net:float, linked?:bool, currencyId?:string}> $prices
     *
     * @return array<string, array{gross:float, net:float, linked?:bool, currencyId?:string}>
     */
    private function resolveIsoCodes(array $prices): array
    {
        if (empty($this->currencies)) {
            /** @var array<string, string> $currencies */
            $currencies = $this->connection->fetchAllKeyValue('SELECT iso_code, LOWER(HEX(id)) FROM currency');
            $this->currencies = $currencies;
        }

        $mapped = [];
        foreach ($prices as $iso => $value) {
            if ($iso === 'default') {
                $mapped[Defaults::CURRENCY] = $value;

                continue;
            }

            if (\array_key_exists('currencyId', $value)) {
                $mapped[$value['currencyId']] = $value;

                continue;
            }

            if (\array_key_exists($iso, $this->currencies)) {
                $mapped[$this->currencies[$iso]] = $value;
            }
        }

        return $mapped;
    }
}

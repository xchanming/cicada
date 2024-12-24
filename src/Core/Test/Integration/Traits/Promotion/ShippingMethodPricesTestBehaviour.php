<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\Traits\Promotion;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;

/**
 * @deprecated tag:v6.7.0 - reason:becomes-internal - Will be internal in v6.7.0
 */
#[Package('checkout')]
trait ShippingMethodPricesTestBehaviour
{
    /**
     * @var array<mixed>
     */
    private array $oldValues = [];

    /**
     * read all shipping method prices from db, store them in oldValues variable
     * and update all prices to $price value
     *
     * @throws Exception
     */
    public function setNewShippingPrices(Connection $conn, float $price): void
    {
        $rows = $conn->fetchAllAssociative(
            'SELECT id, currency_price FROM shipping_method_price'
        );

        foreach ($rows as $row) {
            if (\array_key_exists($row['id'], $this->oldValues)) {
                continue;
            }
            $this->oldValues[$row['id']] = $row['currency_price'];
        }

        $priceStruct = json_encode([
            'c' . Defaults::CURRENCY => [
                'currencyId' => Defaults::CURRENCY,
                'net' => $price,
                'gross' => $price,
                'linked' => false,
            ],
        ]);

        $conn->executeStatement(
            'UPDATE shipping_method_price SET currency_price=:currencyPrice WHERE id in(:ids)',
            ['currencyPrice' => $priceStruct, 'ids' => array_keys($this->oldValues)],
            ['ids' => ArrayParameterType::BINARY]
        );
    }

    /**
     * restore all prices that have been stored in $oldValues
     *
     * @throws Exception
     */
    private function restorePrices(Connection $conn): void
    {
        foreach ($this->oldValues as $k => $v) {
            $conn->executeStatement(
                'UPDATE shipping_method_price SET currency_price=:currencyPrice WHERE id=:id',
                ['currencyPrice' => $v, 'id' => $k]
            );
        }
    }
}

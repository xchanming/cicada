<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\Traits;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('services-settings')]
trait OrderFixture
{
    use BasicTestDataBehaviour;
    use EntityFixturesBase;

    /**
     * @throws \JsonException
     *
     * @return list<array<string, mixed>>
     */
    private function getOrderData(string $orderId, Context $context): array
    {
        $orderCustomerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();
        $orderLineItemId = Uuid::randomHex();
        $countryStateId = Uuid::randomHex();
        $customerId = Uuid::randomHex();
        $orderNumber = Uuid::randomHex();
        $deliveryId = Uuid::randomHex();

        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('id', TestDefaults::SALES_CHANNEL)),
            $context
        )->first();

        $paymentMethodId = $salesChannel->getPaymentMethodId();
        $shippingMethodId = $salesChannel->getShippingMethodId();
        $salutationId = $this->getValidSalutationId();
        $countryId = $this->getValidCountryId(TestDefaults::SALES_CHANNEL);

        $order = [
            [
                'id' => $orderId,
                'orderNumber' => $orderNumber,
                'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
                'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'stateId' => static::getContainer()->get(InitialStateIdLoader::class)->get(OrderStates::STATE_MACHINE),
                'versionId' => Defaults::LIVE_VERSION,
                'paymentMethodId' => $paymentMethodId,
                'currencyId' => Defaults::CURRENCY,
                'currencyFactor' => 1,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'orderDateTime' => '2019-04-01 08:36:43.267',
                'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'deliveries' => [
                    [
                        'id' => $deliveryId,
                        'stateId' => static::getContainer()->get(InitialStateIdLoader::class)->get(OrderDeliveryStates::STATE_MACHINE),
                        'shippingMethodId' => $shippingMethodId,
                        'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'shippingDateEarliest' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_FORMAT),
                        'shippingDateLatest' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_FORMAT),
                        'shippingOrderAddress' => [
                            'salutationId' => $salutationId,
                            'name' => 'Floy',
                            'zipcode' => '59438-0403',
                            'cityId' => $this->getValidCountryCityId(),
                            'street' => 'street',
                            'country' => [
                                'name' => 'kasachstan',
                                'id' => $countryId,
                            ],
                        ],
                        'trackingCodes' => [
                            'CODE-1',
                            'CODE-2',
                        ],
                        'positions' => [
                            [
                                'price' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                                'orderLineItemId' => $orderLineItemId,
                            ],
                        ],
                    ],
                ],
                'lineItems' => [
                    [
                        'id' => $orderLineItemId,
                        'identifier' => 'test',
                        'quantity' => 1,
                        'type' => 'test',
                        'label' => 'test',
                        'price' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'priceDefinition' => new QuantityPriceDefinition(10, new TaxRuleCollection()),
                        'priority' => 100,
                        'good' => true,
                    ],
                ],
                'deepLinkCode' => 'BwvdEInxOHBbwfRw6oHF1Q_orfYeo9RY',
                'orderCustomerId' => $orderCustomerId,
                'orderCustomer' => [
                    'id' => $orderCustomerId,
                    'email' => 'test@example.com',
                    'name' => 'Noe',
                    'salutationId' => $salutationId,
                    'title' => 'Doc',
                    'customerNumber' => 'Test',
                    'orderVersionId' => Defaults::LIVE_VERSION,
                    'customer' => [
                        'id' => $customerId,
                        'email' => 'test@example.com',
                        'name' => 'Noe',
                        'salutationId' => $salutationId,
                        'title' => 'Doc',
                        'customerNumber' => 'Test',
                        'guest' => true,
                        'group' => ['name' => 'testse2323'],
                        'salesChannelId' => TestDefaults::SALES_CHANNEL,
                        'defaultBillingAddressId' => $addressId,
                        'defaultShippingAddressId' => $addressId,
                        'addresses' => [
                            [
                                'id' => $addressId,
                                'salutationId' => $salutationId,
                                'name' => 'Floy',
                                'zipcode' => '59438-0403',
                                'cityId' => $this->getValidCountryCityId(),
                                'street' => 'street',
                                'countryStateId' => $countryStateId,
                                'country' => [
                                    'name' => 'kasachstan',
                                    'id' => $countryId,
                                    'states' => [
                                        [
                                            'id' => $countryStateId,
                                            'name' => 'oklahoma',
                                            'shortCode' => 'OH',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'billingAddressId' => $addressId,
                'addresses' => [
                    [
                        'salutationId' => $salutationId,
                        'name' => 'Floy',
                        'zipcode' => '59438-0403',
                        'cityId' => $this->getValidCountryCityId(),
                        'street' => 'street',
                        'countryId' => $countryId,
                        'id' => $addressId,
                    ],
                ],
            ],
        ];

        return $order;
    }
}

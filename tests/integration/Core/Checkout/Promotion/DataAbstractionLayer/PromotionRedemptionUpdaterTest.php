<?php

declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Promotion\DataAbstractionLayer;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Promotion\DataAbstractionLayer\PromotionRedemptionUpdater;
use Cicada\Core\Checkout\Promotion\Subscriber\PromotionIndividualCodeRedeemer;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Integration\Traits\Promotion\PromotionTestFixtureBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class PromotionRedemptionUpdaterTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;
    use PromotionTestFixtureBehaviour;

    private IdsCollection $ids;

    private Connection $connection;

    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        $this->connection = static::getContainer()->get(Connection::class);
        $this->salesChannelContext = $this->createSalesChannelContext();
    }

    public function testPromotionRedemptionUpdaterUpdateViaIndexer(): void
    {
        $this->createPromotionsAndOrder();

        $updater = static::getContainer()->get(PromotionRedemptionUpdater::class);
        $updater->update(
            [
                $this->ids->get('voucherA'),
                $this->ids->get('voucherB'),
                $this->ids->get('voucherD'),
            ],
            Context::createDefaultContext()
        );

        $this->assertUpdatedCounts();
    }

    public function testPromotionRedemptionUpdaterUpdateViaOrderPlacedEvent(): void
    {
        $this->createPromotionsAndOrder();

        $criteria = new Criteria([$this->ids->get('order')]);
        $criteria->addAssociation('lineItems');

        /** @var OrderEntity|null $order */
        $order = static::getContainer()
            ->get('order.repository')
            ->search($criteria, $this->salesChannelContext->getContext())
            ->first();

        static::assertNotNull($order);

        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->dispatch(new CheckoutOrderPlacedEvent($this->salesChannelContext, $order));

        $this->assertUpdatedCounts();
    }

    public function testItDoesNotFailWithZeroCustomerCount(): void
    {
        $this->createPromotionsAndOrder();

        $voucherA = $this->ids->get('voucherA');

        $connection = static::getContainer()->get(Connection::class);

        // field is write protected - use plain sql here
        $connection->executeStatement(
            'UPDATE `promotion` SET `orders_per_customer_count` = "0" WHERE id = :id',
            ['id' => Uuid::fromHexToBytes($voucherA)]
        );

        $criteria = new Criteria([$this->ids->create('order')]);
        $criteria->addAssociation('lineItems');

        /** @var OrderEntity|null $order */
        $order = static::getContainer()
            ->get('order.repository')
            ->search($criteria, Context::createDefaultContext())
            ->first();

        static::assertNotNull($order);

        $event = new CheckoutOrderPlacedEvent($this->salesChannelContext, $order);

        $updater = static::getContainer()->get(PromotionRedemptionUpdater::class);
        $updater->orderPlaced($event);

        $promotions = $connection->fetchAllAssociative(
            'SELECT `id`, `orders_per_customer_count` FROM `promotion` WHERE `id` = :id',
            ['id' => Uuid::fromHexToBytes($voucherA)]
        );

        $expected_json = json_encode([$this->ids->get('customer') => 1], \JSON_THROW_ON_ERROR);
        static::assertIsString($expected_json);

        static::assertCount(1, $promotions);
        static::assertJsonStringEqualsJsonString(
            $expected_json,
            $promotions[0]['orders_per_customer_count']
        );
    }

    public function testIndividualCodeGotCustomerAssignment(): void
    {
        $this->createPromotionsAndOrder();

        $voucherD = $this->ids->get('voucherD');

        $connection = static::getContainer()->get(Connection::class);

        $criteria = new Criteria([$this->ids->create('order')]);
        $criteria->addAssociation('lineItems');

        /** @var OrderEntity|null $order */
        $order = static::getContainer()
            ->get('order.repository')
            ->search($criteria, Context::createDefaultContext())
            ->first();

        static::assertNotNull($order);

        $event = new CheckoutOrderPlacedEvent($this->salesChannelContext, $order);

        $updater = static::getContainer()->get(PromotionIndividualCodeRedeemer::class);
        $updater->onOrderPlaced($event);

        $promotionIndividualCode = $connection->fetchAllAssociative(
            'SELECT `id`, `payload` FROM `promotion_individual_code` WHERE `promotion_id` = :id',
            ['id' => Uuid::fromHexToBytes($voucherD)]
        );

        $customer = $connection->fetchAllAssociative(
            'SELECT `id`, `title` FROM customer WHERE `id` = :id',
            ['id' => Uuid::fromHexToBytes($this->ids->get('customer'))]
        );

        $promotionIndividualCodePayload = $this->createIndividualCodePayload($order->getId(), $customer[0]);

        $expected_json = json_encode($promotionIndividualCodePayload, \JSON_THROW_ON_ERROR);
        static::assertIsString($expected_json);
        static::assertIsString($promotionIndividualCode[0]['payload']);

        static::assertCount(1, $promotionIndividualCode);
        static::assertJsonStringEqualsJsonString(
            $expected_json,
            $promotionIndividualCode[0]['payload']
        );
    }

    private function createPromotionsAndOrder(): void
    {
        /** @var EntityRepository $promotionRepository */
        $promotionRepository = static::getContainer()->get('promotion.repository');

        /** @var EntityRepository $promotionRepository */
        $promotionIndividualCodeRepository = static::getContainer()->get('promotion_individual_code.repository');

        $voucherA = $this->ids->create('voucherA');
        $voucherB = $this->ids->create('voucherB');
        $voucherD = $this->ids->create('voucherD');

        $this->createPromotion($voucherA, $voucherA, $promotionRepository, $this->salesChannelContext);
        $this->createPromotion($voucherB, $voucherB, $promotionRepository, $this->salesChannelContext);
        $this->createPromotion($voucherD, null, $promotionRepository, $this->salesChannelContext);
        $this->createIndividualCode(
            $voucherD,
            'test-FABPB-test',
            $promotionIndividualCodeRepository,
            $this->salesChannelContext->getContext()
        );

        $this->ids->set('customer', $this->createCustomer('johndoe@example.com'));
        $this->createOrder($this->ids->get('customer'));

        $lineItems = $this->connection->fetchAllAssociative('SELECT id FROM order_line_item;');

        static::assertCount(4, $lineItems);
    }

    private function assertUpdatedCounts(): void
    {
        $promotions = $this->connection->fetchAllAssociative('SELECT * FROM promotion;');

        static::assertCount(3, $promotions);

        $actualVoucherA = Uuid::fromBytesToHex($promotions[0]['id']) === $this->ids->get('voucherA') ? $promotions[0] : $promotions[1];
        static::assertNotEmpty($actualVoucherA);
        static::assertEquals('1', $actualVoucherA['order_count']);
        $customerCount = json_decode((string) $actualVoucherA['orders_per_customer_count'], true, 512, \JSON_THROW_ON_ERROR);
        static::assertEquals(1, $customerCount[$this->ids->get('customer')]);

        $actualVoucherD = Uuid::fromBytesToHex($promotions[0]['id']) === $this->ids->get('voucherD') ? $promotions[0] : $promotions[1];
        static::assertNotEmpty($actualVoucherD);
        static::assertEquals('2', $actualVoucherD['order_count']);
        $customerCount = json_decode((string) $actualVoucherD['orders_per_customer_count'], true, 512, \JSON_THROW_ON_ERROR);
        static::assertEquals(2, $customerCount[$this->ids->get('customer')]);

        $actualVoucherB = Uuid::fromBytesToHex($promotions[0]['id']) === $this->ids->get('voucherB') ? $promotions[0] : $promotions[1];
        static::assertNotEmpty($actualVoucherB);
        // VoucherB is used twice, it's mean group by works
        static::assertEquals('2', $actualVoucherB['order_count']);
        $customerCount = json_decode((string) $actualVoucherB['orders_per_customer_count'], true, 512, \JSON_THROW_ON_ERROR);
        static::assertEquals(2, $customerCount[$this->ids->get('customer')]);
    }

    /**
     * @param array<string, string> $customer
     *
     * @return array<string, string>
     */
    private function createIndividualCodePayload(string $orderId, array $customer): array
    {
        return [
            'orderId' => $orderId,
            'customerId' => Uuid::fromBytesToHex($customer['id']),
            'customerName' => $customer['title'],
        ];
    }

    /**
     * @param array<mixed> $options
     */
    private function createSalesChannelContext(array $options = []): SalesChannelContext
    {
        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);

        $token = Uuid::randomHex();

        return $salesChannelContextFactory->create($token, TestDefaults::SALES_CHANNEL, $options);
    }

    private function createOrder(string $customerId): void
    {
        static::getContainer()->get('order.repository')->create(
            [[
                'id' => $this->ids->create('order'),
                'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
                'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'orderCustomer' => [
                    'customerId' => $customerId,
                    'email' => 'test@example.com',
                    'salutationId' => $this->fetchFirstIdFromTable('salutation'),
                    'name' => 'Max',
                ],
                'stateId' => $this->fetchFirstIdFromTable('state_machine_state'),
                'paymentMethodId' => $this->fetchFirstIdFromTable('payment_method'),
                'currencyId' => Defaults::CURRENCY,
                'currencyFactor' => 1.0,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'billingAddressId' => Uuid::randomHex(),
                'addresses' => [
                    [
                        'id' => Uuid::randomHex(),
                        'salutationId' => $this->fetchFirstIdFromTable('salutation'),
                        'name' => 'Max',
                        'street' => 'Ebbinghoff 10',
                        'zipcode' => '48624',
                        'countryId' => $this->fetchFirstIdFromTable('country'),
                    ],
                ],
                'lineItems' => [
                    [
                        'id' => $this->ids->get('VoucherA'),
                        'type' => LineItem::PROMOTION_LINE_ITEM_TYPE,
                        'code' => '',
                        'identifier' => $this->ids->get('VoucherA'),
                        'quantity' => 1,
                        'payload' => [
                            'promotionId' => $this->ids->get('voucherA'),
                            'code' => '',
                            'promotionCodeType' => 'global',
                        ],
                        'promotionId' => $this->ids->get('voucherA'),
                        'label' => 'label',
                        'price' => new CalculatedPrice(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'priceDefinition' => new QuantityPriceDefinition(200, new TaxRuleCollection(), 2),
                    ],
                    [
                        'id' => $this->ids->get('VoucherD'),
                        'type' => LineItem::PROMOTION_LINE_ITEM_TYPE,
                        'code' => null,
                        'identifier' => $this->ids->get('VoucherD'),
                        'quantity' => 1,
                        'payload' => [
                            'promotionId' => $this->ids->get('voucherD'),
                            'code' => 'test-FABPB-test',
                            'promotionCodeType' => 'individual',
                        ],
                        'promotionId' => $this->ids->get('voucherD'),
                        'label' => 'label',
                        'price' => new CalculatedPrice(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'priceDefinition' => new QuantityPriceDefinition(200, new TaxRuleCollection(), 2),
                    ],
                    [
                        'id' => $this->ids->get('VoucherC'),
                        'type' => LineItem::PROMOTION_LINE_ITEM_TYPE,
                        'code' => $this->ids->get('VoucherC'),
                        'identifier' => $this->ids->get('VoucherC'),
                        'payload' => [
                            'promotionId' => $this->ids->get('voucherB'),
                            'code' => $this->ids->get('VoucherC'),
                        ],
                        'promotionId' => $this->ids->get('voucherB'),
                        'quantity' => 1,
                        'label' => 'label',
                        'price' => new CalculatedPrice(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'priceDefinition' => new QuantityPriceDefinition(200, new TaxRuleCollection(), 2),
                    ],
                    [
                        'id' => $this->ids->get('VoucherB'),
                        'type' => LineItem::PROMOTION_LINE_ITEM_TYPE,
                        'code' => $this->ids->get('VoucherB'),
                        'identifier' => $this->ids->get('VoucherB'),
                        'payload' => [
                            'promotionId' => $this->ids->get('voucherB'),
                            'code' => $this->ids->get('VoucherB'),
                        ],
                        'promotionId' => $this->ids->get('voucherB'),
                        'quantity' => 1,
                        'label' => 'label',
                        'price' => new CalculatedPrice(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'priceDefinition' => new QuantityPriceDefinition(200, new TaxRuleCollection(), 2),
                    ],
                ],
                'deliveries' => [],
                'context' => '{}',
                'payload' => '{}',
            ]],
            Context::createDefaultContext()
        );
    }

    private function fetchFirstIdFromTable(string $table): string
    {
        return Uuid::fromBytesToHex((string) $this->connection->fetchOne('SELECT id FROM ' . $table . ' LIMIT 1'));
    }
}

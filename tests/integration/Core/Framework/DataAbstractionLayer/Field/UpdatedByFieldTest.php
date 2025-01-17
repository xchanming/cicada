<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class UpdatedByFieldTest extends TestCase
{
    use DataAbstractionLayerFieldTestBehaviour;
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<OrderCollection>
     */
    private EntityRepository $orderRepository;

    protected function setUp(): void
    {
        $this->orderRepository = static::getContainer()->get('order.repository');
    }

    public function testUpdatedByNotUpdateWithWrongScope(): void
    {
        $userId = $this->fetchFirstIdFromTable('user');
        $context = $this->getAdminContext($userId);

        $payload = $this->createOrderPayload();
        $this->orderRepository->create([$payload], $context);

        $this->orderRepository->update([
            [
                'id' => $payload['id'],
                'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
        ], $context);

        $result = $this->orderRepository->search(
            new Criteria([$payload['id']]),
            $context
        )->getEntities()->first();

        static::assertNotNull($result);
        static::assertNull($result->getUpdatedById());
    }

    public function testUpdatedByNotUpdateWithWrongSource(): void
    {
        $orderRepository = $this->orderRepository;
        $context = Context::createDefaultContext();

        $payload = $this->createOrderPayload();
        $orderRepository->create([$payload], $context);

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($orderRepository, $payload): void {
            $orderRepository->update([
                [
                    'id' => $payload['id'],
                    'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ],
            ], $context);
        });

        $result = $orderRepository->search(
            new Criteria([$payload['id']]),
            $context
        )->getEntities()->first();

        static::assertNotNull($result);
        static::assertNull($result->getUpdatedById());
    }

    public function testCreateUpdatedBy(): void
    {
        $orderRepository = $this->orderRepository;
        $userId = $this->fetchFirstIdFromTable('user');
        $context = $this->getAdminContext($userId);

        $payload = $this->createOrderPayload();
        $orderRepository->create([$payload], $context);

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($orderRepository, $payload): void {
            $orderRepository->update([
                [
                    'id' => $payload['id'],
                    'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ],
            ], $context);
        });

        $result = $orderRepository->search(
            new Criteria([$payload['id']]),
            $context
        )->getEntities()->first();

        static::assertNotNull($result);
        static::assertEquals($userId, $result->getUpdatedById());
    }

    private function getAdminContext(string $userId): Context
    {
        $source = new AdminApiSource($userId);
        $source->setPermissions([
            'order:list',
            'order:create',
            'order:update',
            'order_customer:create',
            'order_address:create',
        ]);

        return new Context($source);
    }

    /**
     * @return array<string, mixed>
     */
    private function createOrderPayload(): array
    {
        $addressId = Uuid::randomHex();

        return [
            'id' => Uuid::randomHex(),
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'orderCustomer' => [
                'id' => Uuid::randomHex(),
                'email' => 'test@example.com',
                'salutationId' => $this->fetchFirstIdFromTable('salutation'),
                'name' => 'Max',
            ],
            'stateId' => $this->fetchOrderStateId(OrderStates::STATE_OPEN),
            'paymentMethodId' => $this->fetchFirstIdFromTable('payment_method'),
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1.0,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'billingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'salutationId' => $this->fetchFirstIdFromTable('salutation'),
                    'name' => 'Max',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'countryId' => $this->fetchFirstIdFromTable('country'),
                ],
            ],
            'lineItems' => [],
            'deliveries' => [],
            'context' => '{}',
            'payload' => '{}',
        ];
    }

    private function fetchFirstIdFromTable(string $table): string
    {
        return Uuid::fromBytesToHex((string) static::getContainer()->get(Connection::class)->fetchOne('SELECT id FROM ' . $table . ' LIMIT 1'));
    }

    private function fetchOrderStateId(string $orderStateTechnicalName): string
    {
        $id = static::getContainer()->get(Connection::class)->fetchOne(
            'SELECT state_machine_state.id
            FROM state_machine_state
            JOIN state_machine ON state_machine_state.state_machine_id = state_machine.id
            WHERE
                state_machine.technical_name = :orderStateMachineTechnicalName
                AND state_machine_state.technical_name = :orderStateTechnicalName',
            [
                'orderStateMachineTechnicalName' => OrderStates::STATE_MACHINE,
                'orderStateTechnicalName' => $orderStateTechnicalName,
            ]
        );

        return Uuid::fromBytesToHex((string) $id);
    }
}

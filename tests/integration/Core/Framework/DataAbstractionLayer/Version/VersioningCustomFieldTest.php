<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Version;

use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class VersioningCustomFieldTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<OrderCollection>
     */
    private EntityRepository $orderRepository;

    private Context $context;

    protected function setUp(): void
    {
        $this->orderRepository = static::getContainer()->get('order.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testCustomFieldOrderVersioning(): void
    {
        $id = Uuid::randomHex();
        $versionId = $this->context->getVersionId();

        $order = $this->getOrderFixture($id, $versionId);

        // create order + order version and belonging context
        $this->orderRepository->create([$order], $this->context);
        $versionedOrderId = $this->orderRepository->createVersion($id, $this->context);
        $versionedContext = $this->context->createWithVersionId($versionedOrderId);

        $order = $this->orderRepository->search(new Criteria([$id]), $this->context)->getEntities()->first();
        static::assertNotNull($order);
        $versionedOrder = $this->orderRepository->search(new Criteria([$id]), $versionedContext)->getEntities()->first();
        static::assertNotNull($versionedOrder);

        // custom fields should be correctly copied from original order to versioned order
        static::assertSame($order->getCustomFields(), $versionedOrder->getCustomFields());
    }

    public function testCustomFieldMergeBackVersioning(): void
    {
        $id = Uuid::randomHex();
        $versionId = $this->context->getVersionId();

        $order = $this->getOrderFixture($id, $versionId);

        // create order + order version and belonging context
        $this->orderRepository->create([$order], $this->context);
        $versionedOrderId = $this->orderRepository->createVersion($id, $this->context);
        $versionedContext = $this->context->createWithVersionId($versionedOrderId);

        // update versioned order's custom fields
        $this->orderRepository->update([[
            'id' => $id,
            'customFields' => [
                'custom_test' => 1,
                'custom_test_new' => 'this is a test',
            ],
        ]], $versionedContext);

        // merge back version into original order
        $this->orderRepository->merge($versionedOrderId, $this->context);

        $order = $this->orderRepository->search(new Criteria([$id]), $this->context)->getEntities()->first();
        static::assertNotNull($order);

        // custom field update should be applied from versioned order to original order
        static::assertSame(
            [
                'custom_test' => 1,
                'custom_test_new' => 'this is a test',
            ],
            $order->getCustomFields()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrderFixture(string $orderId, string $orderVersionId): array
    {
        $stateId = static::getContainer()->get('state_machine_state.repository')
            ->searchIds((new Criteria())->addFilter(new EqualsFilter('stateMachine.technicalName', OrderStates::STATE_MACHINE)), Context::createDefaultContext())
            ->firstId();
        static::assertIsString($stateId);

        return [
            'id' => $orderId,
            'versionId' => $orderVersionId,
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'customerId' => Uuid::randomHex(),
            'billingAddressId' => Uuid::randomHex(),
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1.00,
            'price' => [
                'netPrice' => 1000.00,
                'totalPrice' => 1000.00,
                'positionPrice' => 1000.00,
                'calculatedTaxes' => [
                    [
                        'tax' => 0.0,
                        'taxRate' => 0.0,
                        'price' => 0.00,
                        'extensions' => [],
                    ],
                ],
                'taxRules' => [
                    [
                        'taxRate' => 0.0,
                        'extensions' => [],
                        'percentage' => 100.0,
                    ],
                ],
                'taxStatus' => 'gross',
                'rawTotal' => 1000.00,
            ],
            'shippingCosts' => [
                'unitPrice' => 0.0,
                'totalPrice' => 0.0,
                'listPrice' => null,
                'referencePrice' => null,
                'quantity' => 1,
                'calculatedTaxes' => [
                    [
                        'tax' => 0.0,
                        'taxRate' => 0.0,
                        'price' => 0.0,
                        'extensions' => [],
                    ],
                ],
                'taxRules' => [
                    [
                        'taxRate' => 0.0,
                        'extensions' => [],
                        'percentage' => 100,
                    ],
                ],
            ],
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'stateId' => $stateId,
            'orderDateTime' => new \DateTime(),
            'customFields' => [
                'custom_test' => 0,
            ],
        ];
    }
}

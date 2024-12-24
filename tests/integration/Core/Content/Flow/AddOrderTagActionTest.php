<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Flow;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Cart\Rule\AlwaysValidRule;
use Cicada\Core\Content\Flow\Dispatching\Action\AddOrderTagAction;
use Cicada\Core\Content\Flow\Dispatching\Action\RemoveOrderTagAction;
use Cicada\Core\Content\Test\Flow\OrderActionTrait;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
#[Package('services-settings')]
class AddOrderTagActionTest extends TestCase
{
    use OrderActionTrait;

    private EntityRepository $flowRepository;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->flowRepository = static::getContainer()->get('flow.repository');

        $this->connection = static::getContainer()->get(Connection::class);

        $this->customerRepository = static::getContainer()->get('customer.repository');

        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $this->ids->create('token'));
    }

    public function testAddOrderTagAction(): void
    {
        $this->createDataTest();

        $this->createCustomerAndLogin();

        $sequenceId = Uuid::randomHex();
        $ruleId = Uuid::randomHex();
        $this->flowRepository->create([[
            'name' => 'Create Order',
            'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            'priority' => 1,
            'active' => true,
            'sequences' => [
                [
                    'id' => $sequenceId,
                    'parentId' => null,
                    'ruleId' => $ruleId,
                    'actionName' => null,
                    'config' => [],
                    'position' => 1,
                    'rule' => [
                        'id' => $ruleId,
                        'name' => 'Test rule',
                        'priority' => 1,
                        'conditions' => [
                            ['type' => (new AlwaysValidRule())->getName()],
                        ],
                    ],
                ],
                [
                    'id' => Uuid::randomHex(),
                    'parentId' => $sequenceId,
                    'ruleId' => null,
                    'actionName' => AddOrderTagAction::getName(),
                    'config' => [
                        'tagIds' => [
                            $this->ids->get('tag_id') => 'test tag',
                            $this->ids->get('tag_id2') => 'test tag2',
                        ],
                    ],
                    'position' => 1,
                    'trueCase' => true,
                ],
                [
                    'id' => Uuid::randomHex(),
                    'parentId' => $sequenceId,
                    'ruleId' => null,
                    'actionName' => AddOrderTagAction::getName(),
                    'config' => [
                        'tagIds' => [
                            $this->ids->get('tag_id3') => 'test tag3',
                        ],
                    ],
                    'position' => 1,
                    'trueCase' => true,
                ],
            ],
        ]], Context::createDefaultContext());

        $this->submitOrder();

        $orderTag = $this->connection->fetchAllAssociative(
            'SELECT tag_id FROM order_tag WHERE tag_id IN (:ids)',
            ['ids' => [Uuid::fromHexToBytes($this->ids->get('tag_id')), Uuid::fromHexToBytes($this->ids->get('tag_id2')), Uuid::fromHexToBytes($this->ids->get('tag_id3'))]],
            ['ids' => ArrayParameterType::BINARY]
        );

        static::assertCount(3, $orderTag);
    }

    public function testAddOrderTagActionWithDuplicateTag(): void
    {
        $this->createDataTest();

        $this->createCustomerAndLogin();

        $sequenceId = Uuid::randomHex();
        $ruleId = Uuid::randomHex();
        $this->flowRepository->create([[
            'name' => 'Create Order',
            'eventName' => CheckoutOrderPlacedEvent::EVENT_NAME,
            'priority' => 1,
            'active' => true,
            'sequences' => [
                [
                    'id' => $sequenceId,
                    'parentId' => null,
                    'ruleId' => $ruleId,
                    'actionName' => null,
                    'config' => [],
                    'position' => 1,
                    'rule' => [
                        'id' => $ruleId,
                        'name' => 'Test rule',
                        'priority' => 1,
                        'conditions' => [
                            ['type' => (new AlwaysValidRule())->getName()],
                        ],
                    ],
                ],
                [
                    'id' => Uuid::randomHex(),
                    'parentId' => $sequenceId,
                    'ruleId' => null,
                    'actionName' => AddOrderTagAction::getName(),
                    'config' => [
                        'tagIds' => [
                            $this->ids->get('tag_id') => 'test tag',
                            $this->ids->get('tag_id2') => 'test tag2',
                        ],
                    ],
                    'position' => 1,
                    'trueCase' => true,
                ],
                [
                    'id' => Uuid::randomHex(),
                    'parentId' => $sequenceId,
                    'ruleId' => null,
                    'actionName' => AddOrderTagAction::getName(),
                    'config' => [
                        'tagIds' => [
                            $this->ids->get('tag_id2') => 'test tag2',
                        ],
                    ],
                    'position' => 2,
                    'trueCase' => true,
                ],
                [
                    'id' => Uuid::randomHex(),
                    'parentId' => $sequenceId,
                    'ruleId' => null,
                    'actionName' => RemoveOrderTagAction::getName(),
                    'config' => [
                        'tagIds' => [
                            $this->ids->get('tag_id') => 'test tag',
                        ],
                    ],
                    'position' => 3,
                    'trueCase' => true,
                ],
                [
                    'id' => Uuid::randomHex(),
                    'parentId' => $sequenceId,
                    'ruleId' => null,
                    'actionName' => AddOrderTagAction::getName(),
                    'config' => [
                        'tagIds' => [
                            $this->ids->get('tag_id3') => 'test tag3',
                        ],
                    ],
                    'position' => 4,
                    'trueCase' => true,
                ],
            ],
        ]], Context::createDefaultContext());

        $this->submitOrder();

        $orderTag = $this->connection->fetchAllAssociative(
            'SELECT tag_id FROM order_tag WHERE tag_id IN (:ids)',
            ['ids' => [Uuid::fromHexToBytes($this->ids->get('tag_id')), Uuid::fromHexToBytes($this->ids->get('tag_id2')), Uuid::fromHexToBytes($this->ids->get('tag_id3'))]],
            ['ids' => ArrayParameterType::BINARY]
        );

        static::assertCount(2, $orderTag);
    }

    private function createDataTest(): void
    {
        $this->addCountriesToSalesChannel();

        $this->prepareProductTest();

        static::getContainer()->get('tag.repository')->create([
            [
                'id' => $this->ids->create('tag_id'),
                'name' => 'test tag',
            ],
            [
                'id' => $this->ids->create('tag_id2'),
                'name' => 'test tag2',
            ],
            [
                'id' => $this->ids->create('tag_id3'),
                'name' => 'test tag3',
            ],
        ], Context::createDefaultContext());

        $shippingMethodRepository = static::getContainer()->get('shipping_method.repository');
        $shippingMethodRepository->create([
            [
                'id' => $this->ids->get('shipping-method'),
                'name' => 'test',
                'technicalName' => 'test',
                'active' => true,
                'deliveryTimeId' => static::getContainer()->get('delivery_time.repository')->searchIds(new Criteria(), Context::createDefaultContext())->firstId(),
                'prices' => [
                    [
                        'currencyId' => Defaults::CURRENCY,
                        'calculation' => 1,
                        'quantityStart' => 1,
                        'quantityEnd' => 100,
                        'currencyPrice' => [
                            [
                                'gross' => 0,
                                'net' => 0,
                                'linked' => false,
                                'currencyId' => Defaults::CURRENCY,
                            ],
                        ],
                    ],
                ],
                'salesChannels' => [
                    ['id' => $this->ids->get('sales-channel')],
                ],
                'salesChannelDefaultAssignments' => [
                    ['id' => $this->ids->get('sales-channel')],
                ],
            ],
        ], Context::createDefaultContext());
    }
}

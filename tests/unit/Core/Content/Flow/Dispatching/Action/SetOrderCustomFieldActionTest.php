<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Dispatching\Action\SetOrderCustomFieldAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('after-sales')]
#[CoversClass(SetOrderCustomFieldAction::class)]
class SetOrderCustomFieldActionTest extends TestCase
{
    private Connection&MockObject $connection;

    private MockObject&EntityRepository $repository;

    private SetOrderCustomFieldAction $action;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->action = new SetOrderCustomFieldAction($this->connection, $this->repository);
    }

    public function testRequirements(): void
    {
        static::assertSame(
            [OrderAware::class],
            $this->action->requirements()
        );
    }

    public function testName(): void
    {
        static::assertSame('action.set.order.custom.field', SetOrderCustomFieldAction::getName());
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $existsData
     * @param array<string, mixed> $expected
     */
    #[DataProvider('actionExecutedProvider')]
    public function testExecutedAction(array $config, array $existsData, array $expected): void
    {
        $orderId = Uuid::randomHex();
        $order = new OrderEntity();
        $order->setId($orderId);
        $order->setUniqueIdentifier($orderId);
        $order->setCustomFields($existsData);

        $context = Context::createDefaultContext();
        $flow = new StorableFlow('', $context, [], [OrderAware::ORDER_ID => $orderId]);
        $flow->setConfig($config);

        $entitySearchResult = new EntitySearchResult(
            OrderDefinition::ENTITY_NAME,
            1,
            new OrderCollection([$order]),
            null,
            new Criteria(),
            $context
        );

        $this->repository->expects(static::once())
            ->method('search')
            ->willReturn($entitySearchResult);
        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->willReturn('custom_field_test');

        $this->repository->expects(static::once())
            ->method('update')
            ->with([['id' => $orderId, 'customFields' => $expected['custom_field_test'] ? $expected : null]]);

        $this->action->handleFlow($flow);
    }

    public function testActionWithNotAware(): void
    {
        $flow = new StorableFlow('', Context::createDefaultContext(), [], []);
        $this->repository->expects(static::never())->method('update');

        $this->action->handleFlow($flow);
    }

    public static function actionExecutedProvider(): \Generator
    {
        yield 'Test aware with upsert config' => [
            [
                'entity' => 'order',
                'customFieldId' => Uuid::randomHex(),
                'customFieldText' => 'custom_field_test',
                'customFieldValue' => ['blue', 'gray'],
                'customFieldSetId' => null,
                'customFieldSetText' => null,
                'option' => 'upsert',
            ],
            [
                'custom_field_test' => ['red', 'green'],
            ],
            [
                'custom_field_test' => ['blue', 'gray'],
            ],
        ];

        yield 'Test aware with create config' => [
            [
                'entity' => 'order',
                'customFieldId' => Uuid::randomHex(),
                'customFieldText' => null,
                'customFieldValue' => ['blue', 'gray'],
                'customFieldSetId' => null,
                'customFieldSetText' => null,
                'option' => 'create',
            ],
            [
                'test' => ['red', 'green'],
            ],
            [
                'test' => ['red', 'green'],
                'custom_field_test' => ['blue', 'gray'],
            ],
        ];

        yield 'Test aware with clear config' => [
            [
                'entity' => 'order',
                'customFieldId' => Uuid::randomHex(),
                'customFieldText' => 'custom_field_test',
                'customFieldValue' => null,
                'customFieldSetId' => null,
                'customFieldSetText' => null,
                'option' => 'clear',
            ],
            [
                'custom_field_test' => ['red', 'green', 'blue'],
            ],
            [
                'custom_field_test' => null,
            ],
        ];

        yield 'Test aware with add config' => [
            [
                'entity' => 'order',
                'customFieldId' => Uuid::randomHex(),
                'customFieldText' => 'custom_field_test',
                'customFieldValue' => ['blue', 'gray'],
                'customFieldSetId' => null,
                'customFieldSetText' => null,
                'option' => 'add',
            ],
            [
                'custom_field_test' => ['red', 'green'],
            ],
            [
                'custom_field_test' => ['red', 'green', 'blue', 'gray'],
            ],
        ];

        yield 'Test aware with remove config' => [
            [
                'entity' => 'order',
                'customFieldId' => Uuid::randomHex(),
                'customFieldText' => 'custom_field_test',
                'customFieldValue' => ['green', 'blue'],
                'customFieldSetId' => null,
                'customFieldSetText' => null,
                'option' => 'remove',
            ],
            [
                'custom_field_test' => ['red', 'green', 'blue'],
            ],
            [
                'custom_field_test' => ['red'],
            ],
        ];
    }
}

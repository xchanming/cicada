<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Content\Flow\Dispatching\Action\SetCustomerGroupCustomFieldAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\CustomerGroupAware;
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
#[CoversClass(SetCustomerGroupCustomFieldAction::class)]
class SetCustomerGroupCustomFieldActionTest extends TestCase
{
    private Connection&MockObject $connection;

    private MockObject&EntityRepository $repository;

    private SetCustomerGroupCustomFieldAction $action;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->action = new SetCustomerGroupCustomFieldAction($this->connection, $this->repository);
    }

    public function testRequirements(): void
    {
        static::assertSame(
            [CustomerGroupAware::class],
            $this->action->requirements()
        );
    }

    public function testName(): void
    {
        static::assertSame('action.set.customer.group.custom.field', SetCustomerGroupCustomFieldAction::getName());
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $existsData
     * @param array<string, mixed> $expected
     */
    #[DataProvider('actionExecutedProvider')]
    public function testExecutedAction(array $config, array $existsData, array $expected): void
    {
        $customerGroupId = Uuid::randomHex();
        $customerGroup = new CustomerGroupEntity();
        $customerGroup->setId($customerGroupId);
        $customerGroup->setUniqueIdentifier($customerGroupId);
        $customerGroup->setCustomFields($existsData);

        $context = Context::createDefaultContext();
        $flow = new StorableFlow('', $context, [], [CustomerGroupAware::CUSTOMER_GROUP_ID => $customerGroupId]);
        $flow->setConfig($config);

        $entitySearchResult = new EntitySearchResult(
            CustomerGroupDefinition::ENTITY_NAME,
            1,
            new CustomerGroupCollection([$customerGroup]),
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
            ->with([['id' => $customerGroupId, 'customFields' => $expected['custom_field_test'] ? $expected : null]]);

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
                'entity' => 'customer_group',
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
                'entity' => 'customer_group',
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
                'entity' => 'customer_group',
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
                'entity' => 'customer_group',
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
                'entity' => 'customer_group',
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

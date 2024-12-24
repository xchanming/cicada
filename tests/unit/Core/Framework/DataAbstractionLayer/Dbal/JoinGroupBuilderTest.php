<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Dbal;

use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\JoinGroup;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\JoinGroupBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\Search\CriteriaPartInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Cicada\Core\System\StateMachine\StateMachineDefinition;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(JoinGroupBuilder::class)]
class JoinGroupBuilderTest extends TestCase
{
    public function testCanGroupProvidedFilters(): void
    {
        $registry = new StaticDefinitionInstanceRegistry(
            [
                ProductDefinition::class,
                ProductCategoryDefinition::class,
                CategoryDefinition::class,
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $definition = $registry->get(ProductDefinition::class);

        $filters = [
            new EqualsFilter('active', true),
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('stock', 10),
                new EqualsFilter('categories.type', 'test'),
            ]),
            new MultiFilter(MultiFilter::CONNECTION_OR),
        ];

        $builder = new JoinGroupBuilder();
        $groupedFilters = $builder->group($filters, $definition, ['product.categories']);

        static::assertCount(3, $groupedFilters);
        static::assertInstanceOf(EqualsFilter::class, $groupedFilters[0]);
        static::assertInstanceOf(EqualsFilter::class, $groupedFilters[1]);
        static::assertInstanceOf(JoinGroup::class, $groupedFilters[2]);
    }

    /**
     * @param array<Filter> $filters
     * @param array<CriteriaPartInterface> $expected
     */
    #[DataProvider('nestedGroupingProvider')]
    public function testNestedGrouping(array $filters, array $expected): void
    {
        $registry = new StaticDefinitionInstanceRegistry(
            [
                OrderDefinition::class,
                OrderTransactionDefinition::class,
                OrderLineItemDefinition::class,
                StateMachineDefinition::class,
                StateMachineStateDefinition::class,
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $builder = new JoinGroupBuilder();

        $groups = $builder->group($filters, $registry->get(OrderDefinition::class));

        static::assertEquals($expected, $groups);
    }

    public static function nestedGroupingProvider(): \Generator
    {
        yield 'Call empty' => [
            [],
            [],
        ];

        yield 'Single filter, no grouping' => [
            [new EqualsFilter('transactions.paymentMethodId', 'paypal')],
            [new EqualsFilter('transactions.paymentMethodId', 'paypal')],
        ];

        yield 'Multiple filters, no grouping' => [
            [
                new EqualsFilter('currencyFactor', 1),
                new EqualsFilter('source', 'foo'),
                new EqualsFilter('shippingTotal', 2.0),
            ],
            [
                new EqualsFilter('currencyFactor', 1),
                new EqualsFilter('source', 'foo'),
                new EqualsFilter('shippingTotal', 2.0),
            ],
        ];

        yield 'Multiple filters, single many filter, no grouping' => [
            [
                new EqualsFilter('currencyFactor', 1),
                new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                    new EqualsFilter('transactions.amount', 1.0),
                ]),
            ],
            [
                new EqualsFilter('currencyFactor', 1),
                new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                new EqualsFilter('transactions.amount', 1.0),
            ],
        ];

        yield 'Multiple filters, multiple many filter, no grouping' => [
            [
                new EqualsFilter('currencyFactor', 1),
                new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                    new EqualsFilter('transactions.amount', 2.0),
                ]),
                new MultiFilter(MultiFilter::CONNECTION_OR, [
                    new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                    new EqualsFilter('transactions.amount', 4.0),
                ]),
            ],
            [
                new EqualsFilter('currencyFactor', 1),
                new JoinGroup([
                    new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                    new EqualsFilter('transactions.amount', 2.0),
                ], 'order.transactions', '_1', MultiFilter::CONNECTION_AND),
                new JoinGroup([
                    new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                    new EqualsFilter('transactions.amount', 4.0),
                ], 'order.transactions', '_2', MultiFilter::CONNECTION_OR),
            ],
        ];

        yield 'Reported issue scenario' => [
            [
                new OrFilter([
                    new AndFilter([
                        new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                        new EqualsFilter('transactions.stateMachineState.technicalName', 'open'),
                    ]),
                    new EqualsFilter('transactions.stateMachineState.technicalName', 'paid'),
                ]),
            ],
            [
                new JoinGroup([
                    new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                    new EqualsFilter('transactions.stateMachineState.technicalName', 'open'),
                ], 'order.transactions', '_1', MultiFilter::CONNECTION_AND),
                new JoinGroup([
                    new EqualsFilter('transactions.stateMachineState.technicalName', 'paid'),
                ], 'order.transactions', '_2', MultiFilter::CONNECTION_OR),
            ],
        ];

        yield 'Multiple many filters, but different path' => [
            [
                new AndFilter([
                    new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                    new EqualsFilter('transactions.amount', 2.0),
                    new EqualsFilter('transactions.stateMachineState.technicalName', 'foo'),
                ]),
                new AndFilter([
                    new EqualsFilter('lineItems.type', 'product'),
                    new EqualsFilter('lineItems.label', 'foo'),
                ]),
            ],
            [
                new EqualsFilter('transactions.paymentMethodId', 'paypal'),
                new EqualsFilter('transactions.amount', 2.0),
                new EqualsFilter('transactions.stateMachineState.technicalName', 'foo'),
                new EqualsFilter('lineItems.type', 'product'),
                new EqualsFilter('lineItems.label', 'foo'),
            ],
        ];
    }
}

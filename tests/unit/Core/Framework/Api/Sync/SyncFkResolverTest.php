<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\Sync;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCategory\ProductCategoryDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Api\ApiException;
use Cicada\Core\Framework\Api\Sync\AbstractFkResolver;
use Cicada\Core\Framework\Api\Sync\SyncFkResolver;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\Tax\TaxDefinition;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(SyncFkResolver::class)]
class SyncFkResolverTest extends TestCase
{
    public function testResolveWithDummy(): void
    {
        $payload = [
            // many-to-one-case
            'taxId' => ['resolver' => 'dummy', 'value' => 't1'],

            // many-to-many-case
            'categories' => [
                ['id' => ['resolver' => 'dummy', 'value' => 'c1']],
                ['id' => ['resolver' => 'dummy', 'value' => 'c2']],
            ],

            // nesting case
            'visibilities' => [
                ['visibility' => 1, 'salesChannelId' => ['resolver' => 'dummy', 'value' => 's1']],
            ],
        ];

        $resolver = new SyncFkResolver(
            new StaticDefinitionInstanceRegistry(
                [
                    ProductDefinition::class,
                    TaxDefinition::class,
                    ProductVisibilityDefinition::class,
                    SalesChannelDefinition::class,
                    CategoryDefinition::class,
                    ProductCategoryDefinition::class,
                ],
                $this->createMock(ValidatorInterface::class),
                $this->createMock(EntityWriteGatewayInterface::class)
            ),
            [new DummyFkResolver()]
        );

        $resolved = $resolver->resolve('ops-1', 'product', [$payload]);

        $expected = [
            'taxId' => 't1',
            'categories' => [
                ['id' => 'c1'],
                ['id' => 'c2'],
            ],
            'visibilities' => [
                ['visibility' => 1, 'salesChannelId' => 's1'],
            ],
        ];

        static::assertCount(1, $resolved);
        static::assertEquals($expected, $resolved[0]);
    }

    /**
     * @param array<array<string, mixed>> $payload
     * @param array<string> $expected
     */
    #[DataProvider('missingResolverProvider')]
    public function testMissingResolverThrowsException(array $payload, array $expected): void
    {
        $resolver = new SyncFkResolver(
            new StaticDefinitionInstanceRegistry(
                [ProductDefinition::class, TaxDefinition::class, CategoryDefinition::class, ProductCategoryDefinition::class],
                $this->createMock(ValidatorInterface::class),
                $this->createMock(EntityWriteGatewayInterface::class)
            ),
            [new DummyFkResolver(), new DoNothingResolver()]
        );

        try {
            $resolver->resolve('ops-1', 'product', $payload);

            static::fail('Case should fail');
        } catch (ApiException $exception) {
            static::assertSame(ApiException::API_INVALID_SYNC_RESOLVERS, $exception->getErrorCode());

            foreach ($expected as $pointer) {
                static::assertStringContainsString($pointer, $exception->getMessage());
            }
        }
    }

    public static function missingResolverProvider(): \Generator
    {
        yield 'Single record, single id missing' => [
            'payload' => [
                [
                    'taxId' => [
                        'resolver' => 'do-nothing',
                        'value' => 't1',
                    ],
                ],
            ],
            'expected' => ['ops-1/0/taxId'],
        ];

        yield 'Single record, multiple ids missing' => [
            'payload' => [
                [
                    'taxId' => [
                        'resolver' => 'do-nothing',
                        'value' => 't1',
                    ],
                    'categories' => [
                        ['id' => ['resolver' => 'do-nothing', 'value' => 'c1']],
                        ['id' => ['resolver' => 'do-nothing', 'value' => 'c2']],
                    ],
                ],
            ],
            'expected' => [
                'ops-1/0/taxId',
                'ops-1/0/categories/0/id',
                'ops-1/0/categories/1/id',
            ],
        ];

        yield 'Multiple records, single id missing' => [
            'payload' => [
                [
                    'taxId' => [
                        'resolver' => 'do-nothing',
                        'value' => 't1',
                    ],
                ],
                [
                    'taxId' => [
                        'resolver' => 'do-nothing',
                        'value' => 't2',
                    ],
                ],
            ],
            'expected' => [
                'ops-1/0/taxId',
                'ops-1/1/taxId',
            ],
        ];

        yield 'Multiple records, multiple ids missing' => [
            'payload' => [
                [
                    'taxId' => [
                        'resolver' => 'do-nothing',
                        'value' => 't1',
                    ],
                    'categories' => [
                        ['id' => ['resolver' => 'do-nothing', 'value' => 'c1']],
                        ['id' => ['resolver' => 'do-nothing', 'value' => 'c2']],
                    ],
                ],
                [
                    'taxId' => [
                        'resolver' => 'do-nothing',
                        'value' => 't2',
                    ],
                    'categories' => [
                        ['id' => ['resolver' => 'do-nothing', 'value' => 'c3']],
                        ['id' => ['resolver' => 'do-nothing', 'value' => 'c4']],
                    ],
                ],
            ],
            'expected' => [
                'ops-1/0/taxId',
                'ops-1/0/categories/0/id',
                'ops-1/0/categories/1/id',
                'ops-1/1/taxId',
                'ops-1/1/categories/0/id',
                'ops-1/1/categories/1/id',
            ],
        ];
    }

    public function testMissingOnNull(): void
    {
        $resolver = new SyncFkResolver(
            new StaticDefinitionInstanceRegistry(
                [ProductDefinition::class, TaxDefinition::class, CategoryDefinition::class, ProductCategoryDefinition::class],
                $this->createMock(ValidatorInterface::class),
                $this->createMock(EntityWriteGatewayInterface::class)
            ),
            [new DummyFkResolver(), new DoNothingResolver()]
        );

        $payload = [
            'taxId' => [
                'resolver' => 'do-nothing',
                'value' => 't1',
                'nullOnMissing' => true,
            ],
        ];

        $resolved = $resolver->resolve('ops-1', 'product', [$payload]);

        static::assertEquals([['taxId' => null]], $resolved);
    }
}

/**
 * @internal
 */
class DummyFkResolver extends AbstractFkResolver
{
    public static function getName(): string
    {
        return 'dummy';
    }

    public function resolve(array $map): array
    {
        foreach ($map as $value) {
            $value->resolved = $value->value;
        }

        return $map;
    }
}

/**
 * @internal
 */
class DoNothingResolver extends AbstractFkResolver
{
    public static function getName(): string
    {
        return 'do-nothing';
    }

    public function resolve(array $map): array
    {
        return $map;
    }
}

<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Cache;

use Cicada\Core\Content\Media\Event\MediaIndexerEvent;
use Cicada\Core\Framework\Adapter\Cache\CacheInvalidationSubscriber;
use Cicada\Core\Framework\Adapter\Cache\CacheInvalidator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Event\NestedEventCollection;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\Snippet\SnippetDefinition;
use Cicada\Core\System\SystemConfig\Event\SystemConfigChangedHook;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CacheInvalidationSubscriber::class)]
#[Group('cache')]
class CacheInvalidationSubscriberTest extends TestCase
{
    /**
     * @var CacheInvalidator&MockObject
     */
    private CacheInvalidator $cacheInvalidator;

    /**
     * @var Connection&MockObject
     */
    private Connection $connection;

    protected function setUp(): void
    {
        $this->cacheInvalidator = $this->createMock(CacheInvalidator::class);
        $this->connection = $this->createMock(Connection::class);
    }

    public function testConsidersKeyOfCachedBaseContextFactoryForInvalidatingContext(): void
    {
        $salesChannelId = Uuid::randomHex();

        $cacheInvalidator = $this->createMock(CacheInvalidator::class);
        $cacheInvalidator->expects(static::once())
            ->method('invalidate')
            ->with(
                [
                    'context-factory-' . $salesChannelId,
                    'base-context-factory-' . $salesChannelId,
                ],
                true
            );

        $subscriber = new CacheInvalidationSubscriber(
            $cacheInvalidator,
            $this->createMock(Connection::class),
            false,
            false,
            true
        );

        $subscriber->invalidateContext(new EntityWrittenContainerEvent(
            Context::createDefaultContext(),
            new NestedEventCollection([
                new EntityWrittenEvent(
                    SalesChannelDefinition::ENTITY_NAME,
                    [
                        new EntityWriteResult(
                            $salesChannelId,
                            [],
                            SalesChannelDefinition::ENTITY_NAME,
                            EntityWriteResult::OPERATION_UPDATE,
                        ),
                    ],
                    Context::createDefaultContext(),
                ),
            ]),
            [],
        ));
    }

    /**
     * @param array<string> $tags
     */
    #[DataProvider('provideTracingTranslationExamples')]
    public function testInvalidateTranslation(bool $enabled, array $tags): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        $cacheInvalidator = $this->createMock(CacheInvalidator::class);
        $cacheInvalidator->expects(static::once())
            ->method('invalidate')
            ->with(
                $tags,
                false
            );

        $subscriber = new CacheInvalidationSubscriber(
            $cacheInvalidator,
            $this->createMock(Connection::class),
            $enabled,
            $enabled,
            true
        );

        $event = $this->createSnippetEvent();

        $subscriber->invalidateSnippets($event);
    }

    public static function provideTracingTranslationExamples(): \Generator
    {
        yield 'enabled' => [
            false,
            [
                'cicada.translator',
            ],
        ];

        yield 'disabled' => [
            true,
            [
                'translator.test',
            ],
        ];
    }

    /**
     * @param array<string> $tags
     */
    #[DataProvider('provideTracingConfigExamples')]
    public function testInvalidateConfig(bool $enabled, array $tags): void
    {
        Feature::skipTestIfActive('cache_rework', $this);

        $cacheInvalidator = $this->createMock(CacheInvalidator::class);
        $cacheInvalidator->expects(static::once())
            ->method('invalidate')
            ->with(
                $tags,
                false
            );

        $subscriber = new CacheInvalidationSubscriber(
            $cacheInvalidator,
            $this->createMock(Connection::class),
            $enabled,
            $enabled,
            true
        );

        $subscriber->invalidateConfigKey(new SystemConfigChangedHook(['test' => '1'], []));
    }

    public function testInvalidateMediaWithoutVariantsWillInvalidateOnlyProducts(): void
    {
        $productId = '123';
        $event = new MediaIndexerEvent([Uuid::randomHex()], Context::createDefaultContext(), []);

        $subscriber = new CacheInvalidationSubscriber(
            $this->cacheInvalidator,
            $this->connection,
            false,
            false,
            true
        );
        $this->connection->method('fetchAllAssociative')
            ->willReturn([['product_id' => $productId, 'version_id' => null]]);

        if (Feature::isActive('cache_rework')) {
            $this->cacheInvalidator->expects(static::once())
                ->method('invalidate')
                ->with(
                    [
                        EntityCacheKeyGenerator::buildProductTag($productId),
                    ],
                    false
                );
        } else {
            $this->cacheInvalidator->expects(static::once())
                ->method('invalidate')
                ->with(
                    [
                        'product-detail-route-' . $productId,
                    ],
                    false
                );
        }

        $subscriber->invalidateMedia($event);
    }

    public function testInvalidateMediaWithVariantsWillInvalidateProductsAndVariants(): void
    {
        $productId = '123';
        $variants = ['456', '789'];
        $event = new MediaIndexerEvent([Uuid::randomHex()], Context::createDefaultContext(), []);

        $subscriber = new CacheInvalidationSubscriber(
            $this->cacheInvalidator,
            $this->connection,
            false,
            false,
            true
        );
        $this->connection->method('fetchAllAssociative')
            ->willReturn([
                ['product_id' => $productId, 'variant_id' => $variants[0]],
                ['product_id' => $productId, 'variant_id' => $variants[1]],
            ]);

        if (Feature::isActive('cache_rework')) {
            $this->cacheInvalidator->expects(static::once())
                ->method('invalidate')
                ->with(
                    [
                        EntityCacheKeyGenerator::buildProductTag($productId),
                        EntityCacheKeyGenerator::buildProductTag($variants[0]),
                        EntityCacheKeyGenerator::buildProductTag($variants[1]),
                    ],
                    false
                );
        } else {
            $this->cacheInvalidator->expects(static::once())
                ->method('invalidate')
                ->with(
                    [
                        'product-detail-route-' . $productId,
                        'product-detail-route-' . $variants[0],
                        'product-detail-route-' . $variants[1],
                    ],
                    false
                );
        }

        $subscriber->invalidateMedia($event);
    }

    public static function provideTracingConfigExamples(): \Generator
    {
        yield 'enabled' => [
            false,
            [
                'global.system.config',
                'system-config',
            ],
        ];

        yield 'disabled' => [
            true,
            [
                'config.test',
                'system-config',
            ],
        ];
    }

    public function createSnippetEvent(): EntityWrittenContainerEvent
    {
        return new EntityWrittenContainerEvent(
            Context::createDefaultContext(),
            new NestedEventCollection([
                new EntityWrittenEvent(
                    SnippetDefinition::ENTITY_NAME,
                    [
                        new EntityWriteResult(
                            Uuid::randomHex(),
                            [
                                'translationKey' => 'test',
                            ],
                            SnippetDefinition::ENTITY_NAME,
                            EntityWriteResult::OPERATION_UPDATE,
                        ),
                    ],
                    Context::createDefaultContext(),
                ),
            ]),
            [],
        );
    }
}

<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Cms;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\CriteriaCollection;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(AbstractProductDetailCmsElementResolver::class)]
class AbstractProductDetailCmsElementResolverTest extends TestCase
{
    public function testCollectReturnsNullIfEntityResolverContextProvided(): void
    {
        $config = new FieldConfigCollection([
            new FieldConfig('product', FieldConfig::SOURCE_STATIC, 'product-id-1'),
        ]);

        $slot = new CmsSlotEntity();
        $slot->setId('slot-1');
        $slot->setFieldConfig($config);

        $context = new EntityResolverContext(
            Generator::generateSalesChannelContext(),
            new Request(),
            new SalesChannelProductDefinition(),
            new SalesChannelProductEntity()
        );

        $resolver = new TestProductDetailCmsElementResolver();
        $collection = $resolver->collect($slot, $context);
        static::assertNull($collection);
    }

    public function testCollectReturnsNullIfNoConfigProvided(): void
    {
        $slot = new CmsSlotEntity();
        $slot->setId('slot-1');

        $context = new ResolverContext(Generator::generateSalesChannelContext(), new Request());

        $resolver = new TestProductDetailCmsElementResolver();
        $collection = $resolver->collect($slot, $context);
        static::assertNull($collection);
    }

    public function testCollectProductCriteria(): void
    {
        $config = new FieldConfigCollection([
            new FieldConfig('product', FieldConfig::SOURCE_STATIC, 'product-id-1'),
        ]);

        $slot = new CmsSlotEntity();
        $slot->setId('slot-1');
        $slot->setFieldConfig($config);

        $context = new ResolverContext(Generator::generateSalesChannelContext(), new Request());

        $resolver = new TestProductDetailCmsElementResolver();
        $collection = $resolver->collect($slot, $context);

        static::assertInstanceOf(CriteriaCollection::class, $collection);

        $elements = $collection->all();
        static::assertCount(1, $elements);
        static::assertArrayHasKey(SalesChannelProductDefinition::class, $elements);

        $definition = $elements[SalesChannelProductDefinition::class];
        static::assertArrayHasKey('product_slot-1', $definition);

        $criteria = $definition['product_slot-1'];
        static::assertInstanceOf(Criteria::class, $criteria);
        static::assertSame('cms::product-detail-static', $criteria->getTitle());
    }

    public function testGetSlotProductReturnsNullIfNoSearchResultProvided(): void
    {
        $slot = new CmsSlotEntity();
        $slot->setId('slot-1');

        $data = new ElementDataCollection();
        $resolver = new TestProductDetailCmsElementResolver();

        static::assertNull($resolver->runGetSlotProduct($slot, $data, 'product-1'));
    }

    public function testGetSlotProduct(): void
    {
        $slot = new CmsSlotEntity();
        $slot->setId('slot-1');

        $result = $this->createMock(EntitySearchResult::class);
        $result->expects(static::once())
            ->method('get')
            ->with('product-1')
            ->willReturn(new SalesChannelProductEntity());

        $data = new ElementDataCollection();
        $data->add('product_slot-1', $result);

        $resolver = new TestProductDetailCmsElementResolver();
        $product = $resolver->runGetSlotProduct($slot, $data, 'product-1');

        static::assertInstanceOf(SalesChannelProductEntity::class, $product);
    }
}

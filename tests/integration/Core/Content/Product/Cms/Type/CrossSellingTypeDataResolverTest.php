<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Cms\Type;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Cms\SalesChannel\Struct\CrossSellingStruct;
use Cicada\Core\Content\Product\Cms\CrossSellingCmsElementResolver;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\CrossSelling\AbstractProductCrossSellingRoute;
use Cicada\Core\Content\Product\SalesChannel\CrossSelling\CrossSellingElementCollection;
use Cicada\Core\Content\Product\SalesChannel\CrossSelling\ProductCrossSellingRouteResponse;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class CrossSellingTypeDataResolverTest extends TestCase
{
    use IntegrationTestBehaviour;

    private CrossSellingCmsElementResolver $crossSellingResolver;

    protected function setUp(): void
    {
        $mock = $this->createMock(AbstractProductCrossSellingRoute::class);
        $mock->method('load')->willReturn(
            new ProductCrossSellingRouteResponse(
                new CrossSellingElementCollection()
            )
        );

        $this->crossSellingResolver = new CrossSellingCmsElementResolver($mock);
    }

    public function testType(): void
    {
        static::assertSame(CrossSellingCmsElementResolver::TYPE, $this->crossSellingResolver->getType());
    }

    public function testCollectWithEmptyConfig(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType(CrossSellingCmsElementResolver::TYPE);
        $slot->setConfig([]);
        $slot->setFieldConfig(new FieldConfigCollection());

        $criteriaCollection = $this->crossSellingResolver->collect($slot, $resolverContext);

        static::assertNull($criteriaCollection);
    }

    public function testCollectWithConfig(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('product', FieldConfig::SOURCE_STATIC, 'product123'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType(CrossSellingCmsElementResolver::TYPE);
        $slot->setFieldConfig($fieldConfig);

        $criteriaCollection = $this->crossSellingResolver->collect($slot, $resolverContext);

        static::assertNotNull($criteriaCollection);
        static::assertCount(1, $criteriaCollection->all());
    }

    public function testEnrichWithEmptyConfig(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());
        $result = new ElementDataCollection();

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType(CrossSellingCmsElementResolver::TYPE);
        $slot->setFieldConfig(new FieldConfigCollection());

        $this->crossSellingResolver->enrich($slot, $resolverContext, $result);

        /** @var CrossSellingStruct|null $crossSellingStruct */
        $crossSellingStruct = $slot->getData();
        static::assertInstanceOf(CrossSellingStruct::class, $crossSellingStruct);
        static::assertNull($crossSellingStruct->getCrossSellings());
    }

    public function testEnrichWithConfig(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());
        $result = new ElementDataCollection();
        $result->add('product_id', new EntitySearchResult(
            'product',
            1,
            new ProductCollection(),
            null,
            new Criteria(),
            $resolverContext->getSalesChannelContext()->getContext()
        ));

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('product', FieldConfig::SOURCE_STATIC, 'product123'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType(CrossSellingCmsElementResolver::TYPE);
        $slot->setFieldConfig($fieldConfig);

        $this->crossSellingResolver->enrich($slot, $resolverContext, $result);

        /** @var CrossSellingStruct|null $crossSellingStruct */
        $crossSellingStruct = $slot->getData();
        static::assertInstanceOf(CrossSellingStruct::class, $crossSellingStruct);
    }

    public function testCollectWithEmptyProductId(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('product', FieldConfig::SOURCE_STATIC, null));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType(CrossSellingCmsElementResolver::TYPE);
        $slot->setFieldConfig($fieldConfig);

        $criteriaCollection = $this->crossSellingResolver->collect($slot, $resolverContext);

        static::assertNull($criteriaCollection);
    }
}

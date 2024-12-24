<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Cms\Type;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Cms\SalesChannel\Struct\TextStruct;
use Cicada\Core\Content\Product\Cms\ProductNameCmsElementResolver;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ProductNameTypeCmsElementResolverTest extends TestCase
{
    use IntegrationTestBehaviour;

    private ProductNameCmsElementResolver $productNameCmsElementResolver;

    protected function setUp(): void
    {
        $this->productNameCmsElementResolver = static::getContainer()->get(ProductNameCmsElementResolver::class);
    }

    public function testType(): void
    {
        static::assertSame('product-name', $this->productNameCmsElementResolver->getType());
    }

    public function testCollect(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('product-name');

        $collection = $this->productNameCmsElementResolver->collect($slot, $resolverContext);

        static::assertNull($collection);
    }

    public function testEnrichWithoutContext(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());
        $result = new ElementDataCollection();

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('product-name');

        $this->productNameCmsElementResolver->enrich($slot, $resolverContext, $result);

        /** @var TextStruct|null $textStruct */
        $textStruct = $slot->getData();
        static::assertInstanceOf(TextStruct::class, $textStruct);
        static::assertNull($textStruct->getContent());
    }

    public function testEnrichEntityResolverContext(): void
    {
        $product = new SalesChannelProductEntity();
        $product->setId('product_01');
        $product->setName('Product 01');
        $resolverContext = new EntityResolverContext($this->createMock(SalesChannelContext::class), new Request(), static::getContainer()->get(SalesChannelProductDefinition::class), $product);
        $result = new ElementDataCollection();

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('product-name');

        $slot->setFieldConfig(new FieldConfigCollection([new FieldConfig('content', FieldConfig::SOURCE_MAPPED, 'product.name')]));

        $this->productNameCmsElementResolver->enrich($slot, $resolverContext, $result);

        /** @var TextStruct|null $textStruct */
        $textStruct = $slot->getData();
        static::assertInstanceOf(TextStruct::class, $textStruct);
        static::assertNotEmpty($textStruct->getContent());
        static::assertEquals('Product 01', $textStruct->getContent());
    }

    public function testWithStaticContentAndMappedVariable(): void
    {
        $category = new CategoryEntity();
        $category->setName('TextCategory');

        $resolverContext = new EntityResolverContext($this->createMock(SalesChannelContext::class), new Request(), $this->createMock(CategoryDefinition::class), $category);
        $result = new ElementDataCollection();

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('content', FieldConfig::SOURCE_STATIC, '<h1>Title {{ category.name }}</h1>'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('text');
        $slot->setConfig([]);
        $slot->setFieldConfig($fieldConfig);

        $this->productNameCmsElementResolver->enrich($slot, $resolverContext, $result);

        /** @var TextStruct|null $textStruct */
        $textStruct = $slot->getData();
        static::assertInstanceOf(TextStruct::class, $textStruct);
        static::assertSame('<h1>Title ' . $category->getName() . '</h1>', $textStruct->getContent());
    }

    public function testWithStaticContentAndMappedVariableNotFound(): void
    {
        $category = new CategoryEntity();
        $category->setName('TextCategory');

        $resolverContext = new EntityResolverContext($this->createMock(SalesChannelContext::class), new Request(), $this->createMock(CategoryDefinition::class), $category);
        $result = new ElementDataCollection();

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('content', FieldConfig::SOURCE_STATIC, '<h1>Title {{ category.unknownProperty }}</h1>'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('text');
        $slot->setConfig([]);
        $slot->setFieldConfig($fieldConfig);

        $this->productNameCmsElementResolver->enrich($slot, $resolverContext, $result);

        /** @var TextStruct|null $textStruct */
        $textStruct = $slot->getData();
        static::assertInstanceOf(TextStruct::class, $textStruct);
        static::assertSame('<h1>Title {{ category.unknownProperty }}</h1>', $textStruct->getContent());
    }
}

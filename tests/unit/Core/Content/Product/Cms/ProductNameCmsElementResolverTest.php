<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Cms;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Cms\SalesChannel\Struct\TextStruct;
use Cicada\Core\Content\Product\Cms\ProductNameCmsElementResolver;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(ProductNameCmsElementResolver::class)]
class ProductNameCmsElementResolverTest extends TestCase
{
    public function testGetType(): void
    {
        $resolver = new ProductNameCmsElementResolver();
        static::assertSame('product-name', $resolver->getType());
    }

    public function testEnrichSetsEmptyTextStructWithoutConfig(): void
    {
        $slot = new CmsSlotEntity();
        $context = new ResolverContext(Generator::createSalesChannelContext(), new Request());
        $data = new ElementDataCollection();

        $resolver = new ProductNameCmsElementResolver();
        $resolver->enrich($slot, $context, $data);

        $data = $slot->getData();
        static::assertInstanceOf(TextStruct::class, $data);
        static::assertNull($data->getContent());
    }

    public function testEnrichStaticContent(): void
    {
        $config = new FieldConfigCollection([
            new FieldConfig('content', FieldConfig::SOURCE_STATIC, 'my-value'),
        ]);

        $slot = new CmsSlotEntity();
        $slot->setFieldConfig($config);

        $context = new ResolverContext(Generator::createSalesChannelContext(), new Request());
        $data = new ElementDataCollection();

        $resolver = new ProductNameCmsElementResolver();
        $resolver->enrich($slot, $context, $data);

        $data = $slot->getData();
        static::assertInstanceOf(TextStruct::class, $data);
        static::assertSame('my-value', $data->getContent());
    }
}

<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Cms\Type;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Cms\SalesChannel\Struct\ProductListingStruct;
use Cicada\Core\Content\Product\Cms\ProductListingCmsElementResolver;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingRouteResponse;
use Cicada\Core\Content\Product\SalesChannel\Sorting\ProductSortingCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ProductListingTypeDataResolverTest extends TestCase
{
    private ProductListingCmsElementResolver $listingResolver;

    protected function setUp(): void
    {
        $mock = $this->createMock(ProductListingRoute::class);
        $mock->method('load')->willReturn(
            new ProductListingRouteResponse(
                new ProductListingResult('product', 0, new ProductCollection(), null, new Criteria(), Context::createDefaultContext())
            )
        );

        $sortingRepository = new StaticEntityRepository([new ProductSortingCollection()]);

        $this->listingResolver = new ProductListingCmsElementResolver($mock, $sortingRepository);
    }

    public function testGetType(): void
    {
        static::assertEquals('product-listing', $this->listingResolver->getType());
    }

    public function testCollect(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('product-listing');

        $collection = $this->listingResolver->collect($slot, $resolverContext);

        static::assertNull($collection);
    }

    public function testEnrichWithoutListingContext(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());
        $result = new ElementDataCollection();

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('product-listing');

        $this->listingResolver->enrich($slot, $resolverContext, $result);

        /** @var ProductListingStruct|null $productListingStruct */
        $productListingStruct = $slot->getData();
        static::assertInstanceOf(ProductListingStruct::class, $productListingStruct);
        static::assertInstanceOf(EntitySearchResult::class, $productListingStruct->getListing());
    }
}

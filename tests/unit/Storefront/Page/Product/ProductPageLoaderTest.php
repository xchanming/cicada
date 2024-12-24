<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\Product;

use Cicada\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockCollection;
use Cicada\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Cicada\Core\Content\Cms\Aggregate\CmsSection\CmsSectionCollection;
use Cicada\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Content\Cms\SalesChannel\Struct\CrossSellingStruct;
use Cicada\Core\Content\Cms\SalesChannel\Struct\ProductDescriptionReviewsStruct;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Cicada\Core\Content\Product\Cms\CrossSellingCmsElementResolver;
use Cicada\Core\Content\Product\Cms\ProductDescriptionReviewsCmsElementResolver;
use Cicada\Core\Content\Product\SalesChannel\CrossSelling\CrossSellingElementCollection;
use Cicada\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Cicada\Core\Content\Product\SalesChannel\Detail\ProductDetailRouteResponse;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewResult;
use Cicada\Core\Content\Product\SalesChannel\Review\RatingMatrix;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Page\GenericPageLoader;
use Cicada\Storefront\Page\Product\ProductPageLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ProductPageLoader::class)]
class ProductPageLoaderTest extends TestCase
{
    /**
     * @deprecated tag:v6.7.0 - Only remove the deprecated parts, not the whole test!
     */
    #[DisabledFeatures(['v6.7.0.0'])]
    public function testItLoadsReviews(): void
    {
        $productId = Uuid::randomHex();
        $request = new Request([], [], ['productId' => $productId]);
        $salesChannelContext = $this->getSalesChannelContext();
        $reviews = $this->getCmsSlotConfig();

        $productPageLoader = $this->getProductPageLoaderWithProduct($productId, $reviews, $request, $salesChannelContext);

        $page = $productPageLoader->load($request, $salesChannelContext);

        $slot = $page->getCmsPage()?->getSections()?->first()?->getBlocks()?->first()?->getSlots()?->first()?->getSlot();
        static::assertIsString($slot);

        static::assertEquals($reviews, json_decode($slot, true, 512, \JSON_THROW_ON_ERROR));

        /** @deprecated tag:v6.7.0 - Remove only everything below this line */
        $reviewsDeprecated = $page->getReviews();
        static::assertNotNull($reviewsDeprecated);
        static::assertCount(1, $reviewsDeprecated);
        $firstReview = $reviewsDeprecated->first();
        static::assertInstanceOf(ProductReviewEntity::class, $firstReview);
        static::assertSame('this product changed my life', $firstReview->getComment());
        $crossSellingDeprecated = $page->getCrossSellings();
        static::assertInstanceOf(CrossSellingElementCollection::class, $crossSellingDeprecated);
        static::assertCount(0, $crossSellingDeprecated);

        $page->assign([
            'reviewLoaderResult' => null,
            'crossSellings' => null,
        ]);

        static::assertNull($page->getReviews());
        static::assertNull($page->getCrossSellings());
    }

    /**
     * @param array<string, array<string, array<string, array<string, array<string, string>>>>> $reviews
     */
    private function getProductPageLoaderWithProduct(string $productId, array $reviews, Request $request, SalesChannelContext $salesChannelContext): ProductPageLoader
    {
        $product = $this->getProductWithReviews($productId, $reviews);

        // set cms page which later will be set by the subscriber
        $product->setCmsPage($this->getCmsPage($product));
        $product->setProductNumber($productId);

        $criteria = (new Criteria())
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category')
            ->addAssociation('media');

        $criteria->getAssociation('media')->addSorting(
            new FieldSorting('position')
        );

        $productDetailRouteMock = $this->createMock(ProductDetailRoute::class);
        $productDetailRouteMock
            ->method('load')
            ->with($productId, $request, $salesChannelContext, $criteria)
            ->willReturn(new ProductDetailRouteResponse($product, null));

        return new ProductPageLoader(
            $this->createMock(GenericPageLoader::class),
            $this->createMock(EventDispatcherInterface::class),
            $productDetailRouteMock
        );
    }

    /**
     * @param array<string, array<string, array<string, array<string, array<string, string>>>>> $reviews
     */
    private function getProductWithReviews(string $productId, array $reviews): SalesChannelProductEntity
    {
        $product = new SalesChannelProductEntity();
        $product->setId($productId);

        // set reviews
        $product->setTranslated($reviews);

        return $product;
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setId('salesChannelId');

        return Generator::createSalesChannelContext(
            salesChannel: $salesChannelEntity,
        );
    }

    private function getCmsPage(SalesChannelProductEntity $productEntity): CmsPageEntity
    {
        $reviewBlock = $this->getReviewBlock($productEntity);
        $crossSellingBlock = $this->getCrossSellingBlock();

        $firstCmsSectionEntity = new CmsSectionEntity();
        $firstCmsSectionEntity->setId(Uuid::randomHex());
        $firstCmsSectionEntity->setBlocks(new CmsBlockCollection([$reviewBlock]));

        $secondCmsSectionEntity = new CmsSectionEntity();
        $secondCmsSectionEntity->setId(Uuid::randomHex());
        $secondCmsSectionEntity->setBlocks(new CmsBlockCollection([$crossSellingBlock]));

        $cmsPageEntity = new CmsPageEntity();
        $cmsPageEntity->setSections(new CmsSectionCollection([$firstCmsSectionEntity, $secondCmsSectionEntity]));

        return $cmsPageEntity;
    }

    /**
     * @return array<string, array<string, array<string, array<string, array<string, string>>>>>
     */
    private function getCmsSlotConfig(): array
    {
        return [
            'data' => [
                'reviews' => [
                    'elements' => [
                        'myReviewElement' => [
                            'title' => 'myReviewTitle',
                            'content' => 'this product changed my life',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getProductReviewResult(): ProductReviewResult
    {
        $review = new ProductReviewEntity();
        $review->setId(Uuid::randomHex());
        $review->setTitle('myReviewTitle');
        $review->setComment('this product changed my life');

        $productReviewResult = new ProductReviewResult(
            ProductReviewDefinition::ENTITY_NAME,
            1,
            new ProductReviewCollection([$review]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
        $productReviewResult->setMatrix(new RatingMatrix([]));

        return $productReviewResult;
    }

    private function getReviewBlock(SalesChannelProductEntity $productEntity): CmsBlockEntity
    {
        $data = new ProductDescriptionReviewsStruct();
        $data->setReviews($this->getProductReviewResult());

        $reviewSlot = new CmsSlotEntity();
        $reviewSlot->setId(Uuid::randomHex());
        $reviewSlot->setSlot(json_encode($productEntity->getTranslated(), \JSON_THROW_ON_ERROR));
        $reviewSlot->setData($data);

        $reviewBlock = new CmsBlockEntity();
        $reviewBlock->setId(Uuid::randomHex());
        $reviewBlock->setType(ProductDescriptionReviewsCmsElementResolver::TYPE);
        $reviewBlock->setSlots(new CmsSlotCollection([$reviewSlot]));

        return $reviewBlock;
    }

    private function getCrossSellingBlock(): CmsBlockEntity
    {
        $crossSellingSlot = new CmsSlotEntity();
        $crossSellingSlot->setId(Uuid::randomHex());
        $crossSellingSlot->setSlot('');
        $crossSellingSlot->setData(new CrossSellingStruct());

        $crossSellingBlock = new CmsBlockEntity();
        $crossSellingBlock->setId(Uuid::randomHex());
        $crossSellingBlock->setType(CrossSellingCmsElementResolver::TYPE);
        $crossSellingBlock->setSlots(new CmsSlotCollection([$crossSellingSlot]));

        return $crossSellingBlock;
    }
}

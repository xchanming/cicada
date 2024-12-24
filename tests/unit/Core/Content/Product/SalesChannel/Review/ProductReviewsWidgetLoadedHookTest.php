<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Review;

use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Cicada\Core\Content\Product\SalesChannel\FindVariant\FindProductVariantRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewSaveRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewLoader;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewResult;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook;
use Cicada\Core\Content\Product\SalesChannel\Review\RatingMatrix;
use Cicada\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Storefront\Page\Product\ProductPageLoader;
use Cicada\Storefront\Page\Product\QuickView\MinimalQuickViewPageLoader;
use Cicada\Tests\Unit\Storefront\Controller\Stub\ProductControllerStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('inventory')]
#[CoversClass(ProductReviewsWidgetLoadedHook::class)]
class ProductReviewsWidgetLoadedHookTest extends TestCase
{
    private MockObject&SystemConfigService $systemConfigServiceMock;

    private MockObject&ProductReviewLoader $productReviewLoaderMock;

    private ProductControllerStub $controller;

    protected function setUp(): void
    {
        $this->systemConfigServiceMock = $this->createMock(SystemConfigService::class);
        $this->productReviewLoaderMock = $this->createMock(ProductReviewLoader::class);

        $this->controller = new ProductControllerStub(
            $this->createMock(ProductPageLoader::class),
            $this->createMock(FindProductVariantRoute::class),
            $this->createMock(MinimalQuickViewPageLoader::class),
            $this->createMock(AbstractProductReviewSaveRoute::class),
            $this->createMock(SeoUrlPlaceholderHandlerInterface::class),
            $this->productReviewLoaderMock,
            $this->systemConfigServiceMock,
            $this->createMock(EventDispatcher::class),
        );
    }

    public function testHookTriggeredWhenProductReviewsWidgetIsLoaded(): void
    {
        Feature::skipTestIfInActive('v6.7.0.0', $this);

        $ids = new IdsCollection();

        $this->systemConfigServiceMock->method('get')->with('core.listing.showReview')->willReturn(true);

        $productId = Uuid::randomHex();
        $parentId = Uuid::randomHex();

        $request = new Request([
            'test' => 'test',
            'productId' => $productId,
            'parentId' => $parentId,
        ]);

        $productReview = new ProductReviewEntity();
        $productReview->setUniqueIdentifier($ids->get('productReview'));
        $reviewResult = new ProductReviewResult(
            'review',
            1,
            new ProductReviewCollection([$productReview]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
        $reviewResult->setMatrix(new RatingMatrix([]));
        $reviewResult->setProductId($productId);
        $reviewResult->setParentId($parentId);

        $this->productReviewLoaderMock->method('load')->with(
            $request,
            $this->createMock(SalesChannelContext::class),
            $productId,
            $parentId
        )->willReturn($reviewResult);

        $this->controller->loadReviews(
            $productId,
            $request,
            $this->createMock(SalesChannelContext::class)
        );

        static::assertInstanceOf(ProductReviewsWidgetLoadedHook::class, $this->controller->calledHook);

        $productReviewsWidgetLoadedHook = $this->controller->calledHook;

        static::assertEquals($reviewResult, $productReviewsWidgetLoadedHook->getReviews());
    }
}

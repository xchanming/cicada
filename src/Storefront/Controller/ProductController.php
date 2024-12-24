<?php declare(strict_types=1);

namespace Cicada\Storefront\Controller;

use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\Exception\ProductNotFoundException;
use Cicada\Core\Content\Product\Exception\ReviewNotActiveExeption;
use Cicada\Core\Content\Product\Exception\VariantNotFoundException;
use Cicada\Core\Content\Product\SalesChannel\FindVariant\AbstractFindProductVariantRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader;
use Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewSaveRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook;
use Cicada\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Storefront\Controller\Exception\StorefrontException;
use Cicada\Storefront\Framework\Page\StorefrontSearchResult;
use Cicada\Storefront\Framework\Routing\RequestTransformer;
use Cicada\Storefront\Page\Product\ProductPageLoadedHook;
use Cicada\Storefront\Page\Product\ProductPageLoader;
use Cicada\Storefront\Page\Product\QuickView\MinimalQuickViewPageLoader;
use Cicada\Storefront\Page\Product\QuickView\ProductQuickViewWidgetLoadedHook;
use Cicada\Storefront\Page\Product\Review\ProductReviewsLoadedEvent as StorefrontProductReviewsLoadedEvent;
use Cicada\Storefront\Page\Product\Review\ProductReviewsWidgetLoadedHook as StorefrontProductReviewsWidgetLoadedHook;
use Cicada\Storefront\Page\Product\Review\ReviewLoaderResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('storefront')]
class ProductController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ProductPageLoader $productPageLoader,
        private readonly AbstractFindProductVariantRoute $findVariantRoute,
        private readonly MinimalQuickViewPageLoader $minimalQuickViewPageLoader,
        private readonly AbstractProductReviewSaveRoute $productReviewSaveRoute,
        private readonly SeoUrlPlaceholderHandlerInterface $seoUrlPlaceholderHandler,
        private readonly AbstractProductReviewLoader $productReviewLoader,
        private readonly SystemConfigService $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    #[Route(path: '/detail/{productId}', name: 'frontend.detail.page', defaults: ['_httpCache' => true], methods: ['GET'])]
    public function index(SalesChannelContext $context, Request $request): Response
    {
        $page = $this->productPageLoader->load($request, $context);

        $this->hook(new ProductPageLoadedHook($page, $context));

        return $this->renderStorefront('@Storefront/storefront/page/content/product-detail.html.twig', ['page' => $page]);
    }

    #[Route(path: '/detail/{productId}/switch', name: 'frontend.detail.switch', defaults: ['XmlHttpRequest' => true, '_httpCache' => true], methods: ['GET'])]
    public function switch(string $productId, Request $request, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $switchedGroup = $request->query->has('switched') ? (string) $request->query->get('switched') : null;

        try {
            $options = json_decode($request->query->get('options', '[]'), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $options = [];
        }

        $variantRequestData = [
            'switchedGroup' => $switchedGroup,
            'options' => $options,
        ];

        $variantRequest = $request->duplicate($variantRequestData);

        try {
            $variantResponse = $this->findVariantRoute->load(
                $productId,
                $variantRequest,
                $salesChannelContext
            );

            $productId = $variantResponse->getFoundCombination()->getVariantId();
        } catch (VariantNotFoundException|ProductNotFoundException) {
            // nth
        }

        $host = $request->attributes->get(RequestTransformer::SALES_CHANNEL_ABSOLUTE_BASE_URL)
            . $request->attributes->get(RequestTransformer::SALES_CHANNEL_BASE_URL);

        $url = $this->seoUrlPlaceholderHandler->replace(
            $this->seoUrlPlaceholderHandler->generate(
                'frontend.detail.page',
                ['productId' => $productId]
            ),
            $host,
            $salesChannelContext
        );

        return new JsonResponse([
            'url' => $url,
            'productId' => $productId,
        ]);
    }

    #[Route(path: '/quickview/{productId}', name: 'widgets.quickview.minimal', defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function quickviewMinimal(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->minimalQuickViewPageLoader->load($request, $context);

        $this->hook(new ProductQuickViewWidgetLoadedHook($page, $context));

        return $this->renderStorefront('@Storefront/storefront/component/product/quickview/minimal.html.twig', ['page' => $page]);
    }

    #[Route(path: '/product/{productId}/rating', name: 'frontend.detail.review.save', defaults: ['XmlHttpRequest' => true, '_loginRequired' => true], methods: ['POST'])]
    public function saveReview(string $productId, RequestDataBag $data, SalesChannelContext $context): Response
    {
        $this->checkReviewsActive($context);

        try {
            $this->productReviewSaveRoute->save($productId, $data, $context);
        } catch (ConstraintViolationException $formViolations) {
            return $this->forwardToRoute('frontend.product.reviews', [
                'productId' => $productId,
                'success' => -1,
                'formViolations' => $formViolations,
                'data' => $data,
            ], ['productId' => $productId]);
        }

        $forwardParams = [
            'productId' => $productId,
            'success' => 1,
            'data' => $data,
            'parentId' => $data->get('parentId'),
        ];

        if ($data->has('id')) {
            $forwardParams['success'] = 2;
        }

        return $this->forwardToRoute('frontend.product.reviews', $forwardParams, ['productId' => $productId]);
    }

    #[Route(path: '/product/{productId}/reviews', name: 'frontend.product.reviews', defaults: ['XmlHttpRequest' => true], methods: ['GET', 'POST'])]
    public function loadReviews(string $productId, Request $request, SalesChannelContext $context): Response
    {
        $this->checkReviewsActive($context);

        $reviews = $this->productReviewLoader->load($request, $context, $productId, $request->get('parentId'));

        $this->hook(new ProductReviewsWidgetLoadedHook($reviews, $context));

        if (!Feature::isActive('v6.7.0.0')) {
            /** @var StorefrontSearchResult<ProductReviewCollection> $storefrontReviews */
            $storefrontReviews = new StorefrontSearchResult(
                $reviews->getEntity(),
                $reviews->getTotal(),
                $reviews->getEntities(),
                $reviews->getAggregations(),
                $reviews->getCriteria(),
                $reviews->getContext()
            );

            $this->eventDispatcher->dispatch(new StorefrontProductReviewsLoadedEvent($storefrontReviews, $context, $request));

            $reviewResult = ReviewLoaderResult::createFrom($storefrontReviews);
            $reviewResult->setMatrix($reviews->getMatrix());
            $reviewResult->setCustomerReview($reviews->getCustomerReview());
            $reviewResult->setTotalReviews($reviews->getTotal());
            $reviewResult->setTotalReviewsInCurrentLanguage($reviews->getTotalReviewsInCurrentLanguage());
            $reviewResult->setProductId($reviews->getProductId());
            $reviewResult->setParentId($reviews->getParentId());

            $this->hook(new StorefrontProductReviewsWidgetLoadedHook($reviewResult, $context));
        }

        return $this->renderStorefront('storefront/component/review/review.html.twig', [
            'reviews' => $reviews,
            'ratingSuccess' => $request->get('success'),
        ]);
    }

    /**
     * @throws ReviewNotActiveExeption
     */
    private function checkReviewsActive(SalesChannelContext $context): void
    {
        $showReview = $this->systemConfigService->get('core.listing.showReview', $context->getSalesChannel()->getId());

        if (!$showReview) {
            throw StorefrontException::reviewNotActive();
        }
    }
}

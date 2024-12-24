<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product\Review;

use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\SalesChannel\Review\Event\ProductReviewsLoadedEvent as CoreProductReviewsLoadedEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Framework\Page\StorefrontSearchResult;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use \Cicada\Core\Content\Product\SalesChannel\Review\Event\ProductReviewsLoadedEvent instead
 */
#[Package('storefront')]
class ProductReviewsLoadedEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    /**
     * @deprecated tag:v6.7.0
     *
     * @var StorefrontSearchResult<ProductReviewCollection>
     */
    protected $searchResult;

    /**
     * @deprecated tag:v6.7.0
     *
     * @var SalesChannelContext
     */
    protected $salesChannelContext;

    /**
     * @deprecated tag:v6.7.0
     *
     * @var Request
     */
    protected $request;

    /**
     * @param StorefrontSearchResult<ProductReviewCollection> $searchResult
     */
    public function __construct(
        StorefrontSearchResult $searchResult,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->searchResult = $searchResult;
        $this->salesChannelContext = $salesChannelContext;
        $this->request = $request;
    }

    /**
     * @return StorefrontSearchResult<ProductReviewCollection>
     */
    public function getSearchResult(): StorefrontSearchResult
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsLoadedEvent::class));

        return $this->searchResult;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsLoadedEvent::class));

        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsLoadedEvent::class));

        return $this->salesChannelContext->getContext();
    }

    public function getRequest(): Request
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsLoadedEvent::class));

        return $this->request;
    }
}

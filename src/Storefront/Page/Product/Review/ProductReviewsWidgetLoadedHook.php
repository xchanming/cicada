<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product\Review;

use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook as CoreProductReviewsWidgetLoadedHook;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Cicada\Core\Framework\Script\Execution\DeprecatedHook;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedHook;

/**
 * Triggered when the ProductReviewsWidget is loaded
 *
 * @hook-use-case data_loading
 *
 * @deprecated tag:v6.7.0 - Will be removed. Use \Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook instead
 * @since 6.4.8.0
 *
 * @final
 */
#[Package('storefront')]
class ProductReviewsWidgetLoadedHook extends PageLoadedHook implements DeprecatedHook
{
    use SalesChannelContextAwareTrait;

    final public const HOOK_NAME = 'product-reviews-loaded';

    public function __construct(
        private readonly ReviewLoaderResult $reviews,
        SalesChannelContext $context
    ) {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsWidgetLoadedHook::class));

        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
    }

    public function getName(): string
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsWidgetLoadedHook::class));

        return self::HOOK_NAME;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsWidgetLoadedHook::class));

        return $this->salesChannelContext;
    }

    public function getReviews(): ReviewLoaderResult
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsWidgetLoadedHook::class));

        return $this->reviews;
    }

    /**
     * @phpstan-ignore cicada.deprecatedClass (this method is called by the core, so it should not trigger a deprecation)
     */
    public static function getDeprecationNotice(): string
    {
        return Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', CoreProductReviewsWidgetLoadedHook::class);
    }
}

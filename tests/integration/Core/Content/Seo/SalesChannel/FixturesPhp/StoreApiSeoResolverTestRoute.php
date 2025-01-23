<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Seo\SalesChannel\FixturesPhp;

use Cicada\Core\Content\Category\SalesChannel\AbstractCategoryRoute;
use Cicada\Core\Content\Category\SalesChannel\CategoryRoute;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\StoreApiRouteScope;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
#[Package('inventory')]
#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StoreApiRouteScope::ID]])]
class StoreApiSeoResolverTestRoute
{
    public function __construct(
        private readonly AbstractCategoryRoute $categoryRoute,
        private readonly AbstractSalesChannelContextFactory $contextFactory,
    ) {
    }

    #[
        Route(
            path: '/store-api/test/store-api-seo-resolver/no-auth-required',
            name: 'store-api.test.store_api_seo_resolver.no_auth_required',
            defaults: ['auth_required' => false],
            methods: [Request::METHOD_GET]
        )
    ]
    public function noAuthRequiredAction(Request $request): StoreApiResponse
    {
        $salesChannelId = $request->get('sales-channel-id');

        return $this->categoryRoute->load(
            CategoryRoute::HOME,
            $request,
            $this->contextFactory->create(Uuid::randomHex(), $salesChannelId)
        );
    }
}

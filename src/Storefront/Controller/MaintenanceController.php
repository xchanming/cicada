<?php declare(strict_types=1);

namespace Cicada\Storefront\Controller;

use Cicada\Core\Framework\Adapter\Kernel\HttpCacheKernel;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Storefront\Framework\Routing\MaintenanceModeResolver;
use Cicada\Storefront\Page\Maintenance\MaintenancePageLoadedHook;
use Cicada\Storefront\Page\Maintenance\MaintenancePageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('storefront')]
class MaintenanceController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly MaintenancePageLoader $maintenancePageLoader,
        private readonly MaintenanceModeResolver $maintenanceModeResolver
    ) {
    }

    #[Route(path: '/maintenance', name: 'frontend.maintenance.page', defaults: ['allow_maintenance' => true, '_httpCache' => true], methods: ['GET'])]
    public function renderMaintenancePage(Request $request, SalesChannelContext $context): ?Response
    {
        $salesChannel = $context->getSalesChannel();

        if ($this->maintenanceModeResolver->shouldRedirectToShop($request)) {
            return $this->redirectToRoute('frontend.home.page');
        }

        $salesChannelId = $salesChannel->getId();
        $maintenanceLayoutId = $this->systemConfigService->getString('core.basicInformation.maintenancePage', $salesChannelId);

        if ($maintenanceLayoutId === '') {
            $response = $this->renderStorefront(
                '@Storefront/storefront/page/error/error-maintenance.html.twig'
            );

            $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE, 'Service Temporarily Unavailable');
            $response->headers->set('Retry-After', '3600');

            $this->addWhitelistIpHeader($request, $response);

            return $response;
        }

        $maintenancePage = $this->maintenancePageLoader->load($maintenanceLayoutId, $request, $context);

        $this->hook(new MaintenancePageLoadedHook($maintenancePage, $context));

        $response = $this->renderStorefront(
            '@Storefront/storefront/page/error/error-maintenance.html.twig',
            ['page' => $maintenancePage]
        );

        $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE, 'Service Temporarily Unavailable');
        $response->headers->set('Retry-After', '3600');

        $this->addWhitelistIpHeader($request, $response);

        return $response;
    }

    /**
     * Route for stand alone cms pages during maintenance
     */
    #[Route(path: '/maintenance/singlepage/{id}', name: 'frontend.maintenance.singlepage', defaults: ['allow_maintenance' => true, '_httpCache' => true], methods: ['GET'])]
    public function renderSinglePage(string $id, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if (!$id) {
            throw RoutingException::missingRequestParameter('id');
        }

        $cmsPage = $this->maintenancePageLoader->load($id, $request, $salesChannelContext);

        $this->hook(new MaintenancePageLoadedHook($cmsPage, $salesChannelContext));

        $response = $this->renderStorefront(
            '@Storefront/storefront/page/content/single-cms-page.html.twig',
            ['page' => $cmsPage]
        );

        $this->addWhitelistIpHeader($request, $response);

        return $response;
    }

    private function addWhitelistIpHeader(Request $request, Response $response): void
    {
        if ($ips = $request->attributes->get(SalesChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE_IP_WHITLELIST)) {
            $ips = implode(',', json_decode($ips, true, flags: \JSON_THROW_ON_ERROR));

            $response->headers->set(HttpCacheKernel::MAINTENANCE_WHITELIST_HEADER, $ips);
        }
    }
}

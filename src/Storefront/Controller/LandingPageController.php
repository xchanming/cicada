<?php declare(strict_types=1);

namespace Cicada\Storefront\Controller;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\LandingPage\LandingPageLoadedHook;
use Cicada\Storefront\Page\LandingPage\LandingPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('buyers-experience')]
class LandingPageController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(private readonly LandingPageLoader $landingPageLoader)
    {
    }

    #[Route(path: '/landingPage/{landingPageId}', name: 'frontend.landing.page', defaults: ['_httpCache' => true], methods: ['GET'])]
    public function index(SalesChannelContext $context, Request $request): Response
    {
        $page = $this->landingPageLoader->load($request, $context);

        $this->hook(new LandingPageLoadedHook($page, $context));

        return $this->renderStorefront('@Storefront/storefront/page/content/index.html.twig', ['page' => $page]);
    }
}

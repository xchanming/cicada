<?php declare(strict_types=1);

namespace Cicada\Storefront\Controller;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Framework\Routing\RequestTransformer;
use Cicada\Storefront\Framework\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('storefront')]
class ContextController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router
    ) {
    }

    #[Route(path: '/checkout/configure', name: 'frontend.checkout.configure', options: ['seo' => false], defaults: ['XmlHttpRequest' => true], methods: ['POST'])]
    public function configure(Request $request, RequestDataBag $data, SalesChannelContext $context): Response
    {
        $this->contextSwitchRoute->switchContext($data, $context);

        return $this->createActionResponse($request);
    }

    #[Route(path: '/checkout/language', name: 'frontend.checkout.switch-language', methods: ['POST'])]
    public function switchLanguage(Request $request, SalesChannelContext $context): RedirectResponse
    {
        $languageId = $request->request->get('languageId');
        if (!$languageId) {
            throw RoutingException::missingRequestParameter('languageId');
        }

        if (!\is_string($languageId) || !Uuid::isValid($languageId)) {
            throw RoutingException::invalidRequestParameter('languageId');
        }

        try {
            $newTokenResponse = $this->contextSwitchRoute->switchContext(
                new RequestDataBag([SalesChannelContextService::LANGUAGE_ID => $languageId]),
                $context
            );
        } catch (ConstraintViolationException) {
            throw RoutingException::languageNotFound($languageId);
        }

        $params = $request->get('redirectParameters', '[]');
        if (\is_string($params)) {
            $params = json_decode($params, true);
        }

        $languageCode = $request->request->get('languageCode_' . $languageId);
        if ($languageCode) {
            $params['_locale'] = $languageCode;
        }

        $route = (string) $request->request->get('redirectTo', 'frontend.home.page');
        if (empty($route) || $this->routeTargetExists($route, $params) === false) {
            $route = 'frontend.home.page';
            $params = [];
        }

        if ($newTokenResponse->getRedirectUrl() === null) {
            return $this->redirectToRoute($route, $params);
        }

        /*
         * possible domains
         *
         * http://cicada.de/de
         * http://cicada.de/en
         * http://cicada.de/fr
         *
         * http://cicada.fr
         * http://cicada.com
         * http://cicada.de
         *
         * http://color.com
         * http://farben.de
         * http://couleurs.fr
         *
         * http://localhost/development/public/de
         * http://localhost/development/public/en
         * http://localhost/development/public/fr
         * http://localhost/development/public/zh-CN
         *
         * http://localhost:8080
         * http://localhost:8080/en
         * http://localhost:8080/fr
         * http://localhost:8080/zh-CN
         */
        $parsedUrl = parse_url($newTokenResponse->getRedirectUrl());

        if (!$parsedUrl) {
            throw RoutingException::languageNotFound($languageId);
        }

        $routerContext = $this->router->getContext();
        $routerContext->setHttpPort($parsedUrl['port'] ?? 80);
        $routerContext->setMethod('GET');
        $routerContext->setHost($parsedUrl['host']);
        $routerContext->setBaseUrl(rtrim($parsedUrl['path'] ?? '', '/'));

        if ($this->requestStack->getMainRequest()) {
            $this->requestStack->getMainRequest()
                ->attributes->set(RequestTransformer::SALES_CHANNEL_BASE_URL, '');
        }

        $url = $this->router->generate($route, $params, Router::ABSOLUTE_URL);

        return new RedirectResponse($url);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function routeTargetExists(string $route, array $params): bool
    {
        try {
            $this->router->generate($route, $params);

            return true;
        } catch (RouteNotFoundException) {
            return false;
        }
    }
}

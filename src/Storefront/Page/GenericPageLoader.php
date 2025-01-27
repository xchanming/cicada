<?php declare(strict_types=1);

namespace Cicada\Storefront\Page;

use Cicada\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Cicada\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Profiling\Profiler;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Storefront\Event\RouteRequest\PaymentMethodRouteRequestEvent;
use Cicada\Storefront\Event\RouteRequest\ShippingMethodRouteRequestEvent;
use Cicada\Storefront\Pagelet\Footer\FooterPageletLoaderInterface;
use Cicada\Storefront\Pagelet\Header\HeaderPageletLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
class GenericPageLoader implements GenericPageLoaderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly HeaderPageletLoaderInterface $headerLoader,
        private readonly FooterPageletLoaderInterface $footerLoader,
        private readonly SystemConfigService $systemConfigService,
        private readonly AbstractPaymentMethodRoute $paymentMethodRoute,
        private readonly AbstractShippingMethodRoute $shippingMethodRoute,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function load(Request $request, SalesChannelContext $context): Page
    {
        return Profiler::trace('generic-page-loader', function () use ($request, $context) {
            $page = new Page();

            $page->setMetaInformation((new MetaInformation())->assign([
                'revisit' => '15 days',
                'robots' => 'index,follow',
                'xmlLang' => $request->attributes->get(SalesChannelRequest::ATTRIBUTE_DOMAIN_LOCALE) ?? '',
                'metaTitle' => $this->systemConfigService->getString('core.basicInformation.shopName', $context->getSalesChannelId()),
            ]));

            if ($request->isXmlHttpRequest()) {
                $this->eventDispatcher->dispatch(
                    new GenericPageLoadedEvent($page, $context, $request)
                );

                return $page;
            }

            if (!Feature::isActive('cache_rework')) {
                $page->setHeader(
                    $this->headerLoader->load($request, $context)
                );

                $page->setFooter(
                    $this->footerLoader->load($request, $context)
                );

                $criteria = new Criteria();
                $criteria->setTitle('generic-page::shipping-methods');

                $event = new ShippingMethodRouteRequestEvent($request, $request->duplicate(), $context, $criteria);
                $this->eventDispatcher->dispatch($event);

                $shippingMethods = $this->shippingMethodRoute
                    ->load($event->getStoreApiRequest(), $context, $event->getCriteria())
                    ->getShippingMethods();

                $page->setSalesChannelShippingMethods($shippingMethods);

                $criteria = new Criteria();
                $criteria->setTitle('generic-page::payment-methods');

                $event = new PaymentMethodRouteRequestEvent($request, $request->duplicate(), $context, $criteria);
                $this->eventDispatcher->dispatch($event);

                $paymentMethods = $this->paymentMethodRoute
                    ->load($event->getStoreApiRequest(), $context, $event->getCriteria())
                    ->getPaymentMethods();

                $page->setSalesChannelPaymentMethods($paymentMethods);
            }

            $this->eventDispatcher->dispatch(
                new GenericPageLoadedEvent($page, $context, $request)
            );

            return $page;
        });
    }
}

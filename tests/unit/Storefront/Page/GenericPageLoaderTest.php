<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page;

use Cicada\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Cicada\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Page\GenericPageLoader;
use Cicada\Storefront\Pagelet\Footer\FooterPageletLoaderInterface;
use Cicada\Storefront\Pagelet\Header\HeaderPageletLoaderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(GenericPageLoader::class)]
class GenericPageLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $headerPageletLoader = $this->createMock(HeaderPageletLoaderInterface::class);
        $headerPageletLoader->expects(static::never())->method('load');

        $footerPageletLoader = $this->createMock(FooterPageletLoaderInterface::class);
        $footerPageletLoader->expects(static::never())->method('load');

        $paymentMethodRoute = $this->createMock(AbstractPaymentMethodRoute::class);
        $paymentMethodRoute->expects(static::never())->method('load');

        $shippingMethodRoute = $this->createMock(AbstractShippingMethodRoute::class);
        $shippingMethodRoute->expects(static::never())->method('load');

        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->method('getString')->willReturn('Cicada');

        $loader = new GenericPageLoader(
            $headerPageletLoader,
            $footerPageletLoader,
            $systemConfigService,
            $paymentMethodRoute,
            $shippingMethodRoute,
            $this->createMock(EventDispatcherInterface::class)
        );

        $request = new Request(attributes: [SalesChannelRequest::ATTRIBUTE_DOMAIN_LOCALE => 'en-GB']);

        $metaInformation = $loader->load($request, Generator::generateSalesChannelContext())->getMetaInformation();
        static::assertNotNull($metaInformation);
        static::assertSame('Cicada', $metaInformation->getMetaTitle());
        static::assertSame('en-GB', $metaInformation->getXmlLang());
    }
}

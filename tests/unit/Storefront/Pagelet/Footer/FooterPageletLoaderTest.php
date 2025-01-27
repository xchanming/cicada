<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Pagelet\Footer;

use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Cicada\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Cicada\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Cicada\Core\Checkout\Shipping\SalesChannel\ShippingMethodRouteResponse;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Category\Service\NavigationLoaderInterface;
use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Content\Category\Tree\TreeItem;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Pagelet\Footer\FooterPageletLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(FooterPageletLoader::class)]
class FooterPageletLoaderTest extends TestCase
{
    public function testLoad(): void
    {
        $serviceMenuId = Uuid::randomHex();
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setServiceCategoryId($serviceMenuId);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $navigationLoader = $this->createMock(NavigationLoaderInterface::class);

        $categoryId1 = Uuid::randomHex();
        $categoryId2 = Uuid::randomHex();
        $category1 = (new CategoryEntity())->assign(['id' => $categoryId1]);
        $category2 = (new CategoryEntity())->assign(['id' => $categoryId2]);
        $navigationLoader->method('load')->willReturnMap(
            [
                [
                    $serviceMenuId,
                    $salesChannelContext,
                    $serviceMenuId,
                    1,
                    new Tree($category2, [new TreeItem($category1, []), new TreeItem($category2, [])]),
                ],
            ]
        );

        $paymentMethodCollection = new PaymentMethodCollection();
        $paymentMethodRoute = $this->createMock(AbstractPaymentMethodRoute::class);
        $paymentMethodRoute->method('load')->willReturn(new PaymentMethodRouteResponse(
            new EntitySearchResult(
                PaymentMethodDefinition::ENTITY_NAME,
                0,
                $paymentMethodCollection,
                null,
                new Criteria(),
                $salesChannelContext->getContext()
            )
        ));

        $shippingMethodCollection = new ShippingMethodCollection();
        $shippingMethodRoute = $this->createMock(AbstractShippingMethodRoute::class);
        $shippingMethodRoute->method('load')->willReturn(new ShippingMethodRouteResponse(
            new EntitySearchResult(
                ShippingMethodDefinition::ENTITY_NAME,
                0,
                $shippingMethodCollection,
                null,
                new Criteria(),
                $salesChannelContext->getContext()
            )
        ));

        $footerPageletLoader = new FooterPageletLoader($eventDispatcher, $navigationLoader, $paymentMethodRoute, $shippingMethodRoute);
        $footer = $footerPageletLoader->load(new Request(), $salesChannelContext);

        $serviceMenu = $footer->getServiceMenu();
        static::assertNotNull($serviceMenu);
        static::assertCount(2, $serviceMenu);
        static::assertSame($category1, $serviceMenu->get($categoryId1));
        static::assertSame($category2, $serviceMenu->get($categoryId2));

        static::assertSame($paymentMethodCollection, $footer->getPaymentMethods());
        static::assertSame($shippingMethodCollection, $footer->getShippingMethods());
    }
}

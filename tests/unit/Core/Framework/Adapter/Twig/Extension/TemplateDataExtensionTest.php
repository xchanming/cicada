<?php

declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig\Extension;

use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\Doctrine\FakeConnection;
use Cicada\Storefront\Controller\NavigationController;
use Cicada\Storefront\Framework\Twig\NavigationInfo;
use Cicada\Storefront\Framework\Twig\TemplateDataExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversClass(TemplateDataExtension::class)]
class TemplateDataExtensionTest extends TestCase
{
    public function testGetGlobalsWithoutRequest(): void
    {
        $globals = (new TemplateDataExtension(
            new RequestStack(),
            true,
            new FakeConnection([])
        ))->getGlobals();

        static::assertSame([], $globals);
    }

    public function testGetGlobalsWithoutSalesChannelContextInRequest(): void
    {
        $globals = (new TemplateDataExtension(
            new RequestStack([new Request()]),
            true,
            new FakeConnection([])
        ))->getGlobals();

        static::assertSame([], $globals);
    }

    public function testGetGlobals(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $activeRoute = 'frontend.home.page';
        $controller = NavigationController::class;
        $themeId = Uuid::randomHex();
        $expectedMinSearchLength = 3;

        $request = new Request(attributes: [
            PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT => $salesChannelContext,
            '_route' => $activeRoute,
            '_controller' => $controller . '::index',
            SalesChannelRequest::ATTRIBUTE_THEME_ID => $themeId,
        ]);

        $globals = (new TemplateDataExtension(
            new RequestStack([$request]),
            true,
            new FakeConnection([['minSearchLength' => (string) $expectedMinSearchLength]])
        ))->getGlobals();

        static::assertArrayHasKey('cicada', $globals);
        static::assertArrayHasKey('dateFormat', $globals['cicada']);
        static::assertSame('Y-m-d\TH:i:sP', $globals['cicada']['dateFormat']);
        static::assertArrayHasKey('navigation', $globals['cicada']);
        static::assertInstanceOf(NavigationInfo::class, $globals['cicada']['navigation']);
        static::assertSame($salesChannelContext->getSalesChannel()->getNavigationCategoryId(), $globals['cicada']['navigation']->id);
        static::assertArrayHasKey('minSearchLength', $globals['cicada']);
        static::assertSame($expectedMinSearchLength, $globals['cicada']['minSearchLength']);
        static::assertArrayHasKey('showStagingBanner', $globals['cicada']);
        static::assertTrue($globals['cicada']['showStagingBanner']);

        static::assertArrayHasKey('themeId', $globals);
        static::assertSame($themeId, $globals['themeId']);

        static::assertArrayHasKey('controllerName', $globals);
        static::assertSame('Navigation', $globals['controllerName']);
        static::assertArrayHasKey('controllerAction', $globals);
        static::assertSame('index', $globals['controllerAction']);

        static::assertArrayHasKey('context', $globals);
        static::assertSame($salesChannelContext, $globals['context']);

        static::assertArrayHasKey('activeRoute', $globals);
        static::assertSame($activeRoute, $globals['activeRoute']);

        static::assertArrayHasKey('formViolations', $globals);
        static::assertNull($globals['formViolations']);
    }
}

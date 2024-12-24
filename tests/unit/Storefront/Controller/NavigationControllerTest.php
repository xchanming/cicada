<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Controller\NavigationController;
use Cicada\Storefront\Page\Navigation\NavigationPage;
use Cicada\Storefront\Page\Navigation\NavigationPageLoaderInterface;
use Cicada\Storefront\Pagelet\Menu\Offcanvas\MenuOffcanvasPageletLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[CoversClass(NavigationController::class)]
class NavigationControllerTest extends TestCase
{
    private NavigationPageLoaderInterface&MockObject $pageLoader;

    private MenuOffcanvasPageletLoaderInterface&MockObject $offCanvasLoader;

    private NavigationControllerTestClass $controller;

    protected function setUp(): void
    {
        $this->pageLoader = $this->createMock(NavigationPageLoaderInterface::class);
        $this->offCanvasLoader = $this->createMock(MenuOffcanvasPageletLoaderInterface::class);

        $this->controller = new NavigationControllerTestClass($this->pageLoader, $this->offCanvasLoader);
    }

    public function testHomeRendersStorefront(): void
    {
        $this->pageLoader->method('load')
            ->willReturn(new NavigationPage());

        $request = new Request();
        $context = Generator::createSalesChannelContext();

        $response = $this->controller->home($request, $context);
        static::assertInstanceOf(Response::class, $response);
        static::assertSame('@Storefront/storefront/page/content/index.html.twig', $this->controller->renderStorefrontView);
    }

    public function testIndexRendersStorefront(): void
    {
        $this->pageLoader->method('load')
            ->willReturn(new NavigationPage());

        $request = new Request([
            'navigationId' => Uuid::randomHex(),
        ]);
        $context = Generator::createSalesChannelContext();

        $this->controller->index($context, $request);
        static::assertSame('@Storefront/storefront/page/content/index.html.twig', $this->controller->renderStorefrontView);
    }

    public function testOffcanvasRendersStorefront(): void
    {
        $request = new Request();
        $context = Generator::createSalesChannelContext();

        $response = $this->controller->offcanvas($request, $context);
        static::assertSame('noindex', $response->headers->get('x-robots-tag'));
        static::assertSame('@Storefront/storefront/layout/navigation/offcanvas/navigation-pagelet.html.twig', $this->controller->renderStorefrontView);
    }
}

/**
 * @internal
 */
class NavigationControllerTestClass extends NavigationController
{
    use StorefrontControllerMockTrait;
}

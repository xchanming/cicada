<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Routing;

use Cicada\Core\Framework\Routing\RouteEventSubscriber;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Kernel;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Event\StorefrontRenderEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
#[CoversClass(RouteEventSubscriber::class)]
class RouteEventSubscriberTest extends TestCase
{
    public function testRequestEvent(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'frontend.home.page');

        $event = new RequestEvent($this->createMock(Kernel::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('frontend.home.page.request', $listener);

        $subscriber = new RouteEventSubscriber($dispatcher);
        $subscriber->request($event);
    }

    public function testResponseEvent(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'frontend.home.page');

        $event = new ResponseEvent($this->createMock(Kernel::class), $request, HttpKernelInterface::MAIN_REQUEST, new Response());

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('frontend.home.page.response', $listener);

        $subscriber = new RouteEventSubscriber($dispatcher);
        $subscriber->response($event);
    }

    public function testRenderEvent(): void
    {
        if (!\class_exists(StorefrontRenderEvent::class)) {
            // storefront dependency not installed
            return;
        }

        $request = new Request();
        $request->attributes->set('_route', 'frontend.home.page');

        $event = new StorefrontRenderEvent('', [], $request, $this->createMock(SalesChannelContext::class));

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('frontend.home.page.render', $listener);

        $subscriber = new RouteEventSubscriber($dispatcher);
        $subscriber->render($event);
    }
}

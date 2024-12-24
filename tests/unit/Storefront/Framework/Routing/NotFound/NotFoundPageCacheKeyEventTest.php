<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\Routing\NotFound;

use Cicada\Core\Framework\Context;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Framework\Routing\NotFound\NotFoundPageCacheKeyEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(NotFoundPageCacheKeyEvent::class)]
class NotFoundPageCacheKeyEventTest extends TestCase
{
    public function testEvent(): void
    {
        $request = new Request();
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getContext')->willReturn(Context::createDefaultContext());

        $event = new NotFoundPageCacheKeyEvent('test', $request, $context);

        static::assertSame('test', $event->getKey());
        static::assertSame($context->getContext(), $event->getContext());
        static::assertSame($context, $event->getSalesChannelContext());
        static::assertSame($request, $event->getRequest());

        $event->setKey('test2');
        static::assertSame('test2', $event->getKey());
    }
}

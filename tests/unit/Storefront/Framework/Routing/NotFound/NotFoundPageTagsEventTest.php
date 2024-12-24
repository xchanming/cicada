<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\Routing\NotFound;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Framework\Routing\NotFound\NotFoundPageTagsEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(NotFoundPageTagsEvent::class)]
class NotFoundPageTagsEventTest extends TestCase
{
    public function testEvent(): void
    {
        $request = new Request();
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getContext')->willReturn(Context::createDefaultContext());

        $event = new NotFoundPageTagsEvent(['test'], $request, $context);

        static::assertSame(['test'], $event->getTags());
        static::assertSame($context->getContext(), $event->getContext());
        static::assertSame($context, $event->getSalesChannelContext());
        static::assertSame($request, $event->getRequest());

        $event->addTags(['test2']);
        static::assertSame(['test', 'test2'], $event->getTags());
    }
}

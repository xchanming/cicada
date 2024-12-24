<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(SalesChannelContextCreatedEvent::class)]
class SalesChannelContextCreatedEventTest extends TestCase
{
    public function testEventReturnsAllNeededData(): void
    {
        $token = 'foo';
        $context = Context::createDefaultContext();
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext->method('getContext')->willReturn($context);

        $event = new SalesChannelContextCreatedEvent($salesChannelContext, $token);
        static::assertSame($token, $event->getUsedToken());
        static::assertSame($context, $event->getContext());
        static::assertSame($salesChannelContext, $event->getSalesChannelContext());
    }
}

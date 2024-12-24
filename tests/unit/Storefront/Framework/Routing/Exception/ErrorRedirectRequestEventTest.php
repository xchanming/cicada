<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\Routing\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Storefront\Framework\Routing\Exception\ErrorRedirectRequestEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ErrorRedirectRequestEvent::class)]
class ErrorRedirectRequestEventTest extends TestCase
{
    public function testEvent(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();
        $exception = new \Exception();

        $event = new ErrorRedirectRequestEvent($request, $exception, $context);

        static::assertSame($context, $event->getContext());
        static::assertSame($exception, $event->getException());
        static::assertSame($request, $event->getRequest());
    }
}

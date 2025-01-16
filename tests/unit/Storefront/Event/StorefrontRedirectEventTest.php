<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Event\StorefrontRedirectEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(StorefrontRedirectEvent::class)]
class StorefrontRedirectEventTest extends TestCase
{
    public function testMinimalConstructor(): void
    {
        $event = new StorefrontRedirectEvent('test_route');

        static::assertSame('test_route', $event->getRoute());
        static::assertSame([], $event->getParameters());
        static::assertSame(Response::HTTP_FOUND, $event->getStatus());
    }

    public function testConstructor(): void
    {
        $event = new StorefrontRedirectEvent('test_route', ['test_parameter' => 'test_value'], 500);

        static::assertSame('test_route', $event->getRoute());
        static::assertSame(['test_parameter' => 'test_value'], $event->getParameters());
        static::assertSame(500, $event->getStatus());
    }

    public function testSetters(): void
    {
        $event = new StorefrontRedirectEvent('test_route');

        $event->setRoute('test_route_2');
        $event->setParameters(['test_parameter' => 'test_value']);
        $event->setStatus(500);

        static::assertSame('test_route_2', $event->getRoute());
        static::assertSame(['test_parameter' => 'test_value'], $event->getParameters());
        static::assertSame(500, $event->getStatus());
    }
}

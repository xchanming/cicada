<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Service\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Service\Event\ServiceOutdatedEvent;

/**
 * @internal
 */
#[CoversClass(ServiceOutdatedEvent::class)]
class ServiceOutdatedEventTest extends TestCase
{
    public function testAccessors(): void
    {
        $context = new Context(new SystemSource());
        $e = new ServiceOutdatedEvent('MyCoolService', $context);

        static::assertSame('MyCoolService', $e->serviceName);
        static::assertSame($context, $e->getContext());
    }
}

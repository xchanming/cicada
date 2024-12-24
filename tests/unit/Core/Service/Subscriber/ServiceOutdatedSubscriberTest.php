<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Service\Subscriber;

use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Service\Event\ServiceOutdatedEvent;
use Cicada\Core\Service\ServiceLifecycle;
use Cicada\Core\Service\Subscriber\ServiceOutdatedSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ServiceOutdatedSubscriber::class)]
class ServiceOutdatedSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $subscriber = new ServiceOutdatedSubscriber(static::createMock(ServiceLifecycle::class));

        static::assertSame(
            [ServiceOutdatedEvent::class => 'updateService'],
            $subscriber->getSubscribedEvents()
        );
    }

    public function testUpdateServiceDelegatesToServiceLifecycle(): void
    {
        $context = new Context(new SystemSource());
        $serviceLifecycle = static::createMock(ServiceLifecycle::class);
        $serviceLifecycle->expects(static::once())
            ->method('update')
            ->with('MyCoolService', $context);

        $subscriber = new ServiceOutdatedSubscriber($serviceLifecycle);
        $subscriber->updateService(new ServiceOutdatedEvent('MyCoolService', $context));
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Service\Subscriber;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Service\Event\ServiceOutdatedEvent;
use Cicada\Core\Service\ServiceLifecycle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class ServiceOutdatedSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ServiceLifecycle $serviceLifecycle)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ServiceOutdatedEvent::class => 'updateService',
        ];
    }

    public function updateService(ServiceOutdatedEvent $event): void
    {
        $this->serviceLifecycle->update($event->serviceName, $event->getContext());
    }
}

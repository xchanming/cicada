<?php declare(strict_types=1);

namespace Cicada\Administration\Notification\Subscriber;

use Cicada\Administration\Notification\NotificationService;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Update\Event\UpdatePostFinishEvent;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('services-settings')]
class UpdateSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UpdatePostFinishEvent::class => [
                ['updateFinishedDone', -9999],
            ],
        ];
    }

    /**
     * @internal
     */
    public function updateFinishedDone(UpdatePostFinishEvent $event): void
    {
        if ($event->getPostUpdateMessage() === '') {
            return;
        }

        $source = $event->getContext()->getSource();
        $integrationId = null;
        $createdByUserId = null;
        if ($source instanceof AdminApiSource) {
            $integrationId = $source->getIntegrationId();
            $createdByUserId = $source->getUserId();
        }

        $this->notificationService->createNotification(
            [
                'id' => Uuid::randomHex(),
                'status' => 'warning',
                'message' => $event->getPostUpdateMessage(),
                'adminOnly' => true,
                'requiredPrivileges' => [],
                'createdByIntegrationId' => $integrationId,
                'createdByUserId' => $createdByUserId,
            ],
            $event->getContext()
        );
    }
}

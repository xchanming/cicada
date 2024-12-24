<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Flow\Action;

use Cicada\Core\Framework\App\Aggregate\FlowAction\AppFlowActionEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class AppFlowActionLoadedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'app_flow_action.loaded' => 'unserialize',
        ];
    }

    public function unserialize(EntityLoadedEvent $event): void
    {
        foreach ($event->getEntities() as $appFlowAction) {
            if (!$appFlowAction instanceof AppFlowActionEntity) {
                continue;
            }
            $iconRaw = $appFlowAction->getIconRaw();

            if ($iconRaw !== null) {
                $appFlowAction->setIcon(base64_encode($iconRaw));
            }
        }
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Subscriber;

use Cicada\Core\Framework\App\Aggregate\AppScriptCondition\AppScriptConditionEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class AppScriptConditionConstraintsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'app_script_condition.loaded' => 'unserialize',
        ];
    }

    public function unserialize(EntityLoadedEvent $event): void
    {
        foreach ($event->getEntities() as $entity) {
            if (!$entity instanceof AppScriptConditionEntity) {
                continue;
            }

            $constraints = $entity->getConstraints();
            if ($constraints === null || !\is_string($constraints)) {
                continue;
            }

            $entity->setConstraints(unserialize($constraints));
        }
    }
}

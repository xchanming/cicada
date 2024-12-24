<?php declare(strict_types=1);

namespace Cicada\Core\System\Currency;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class CurrencyLoadSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CurrencyEvents::CURRENCY_LOADED_EVENT => 'setDefault',
            'currency.partial_loaded' => 'setDefault',
        ];
    }

    public function setDefault(EntityLoadedEvent $event): void
    {
        foreach ($event->getEntities() as $entity) {
            $entity->assign([
                'isSystemDefault' => ($entity->get('id') === Defaults::CURRENCY),
            ]);
        }
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Subscriber;

use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Plugin\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class PluginLoadedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PLUGIN_LOADED_EVENT => [
                ['unserialize'],
            ],
        ];
    }

    public function unserialize(EntityLoadedEvent $event): void
    {
        /** @var PluginEntity $plugin */
        foreach ($event->getEntities() as $plugin) {
            if ($plugin->getIconRaw()) {
                $plugin->setIcon(base64_encode($plugin->getIconRaw()));
            }
        }
    }
}

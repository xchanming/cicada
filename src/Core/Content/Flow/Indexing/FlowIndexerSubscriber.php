<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Indexing;

use Cicada\Core\Framework\App\Event\AppActivatedEvent;
use Cicada\Core\Framework\App\Event\AppDeactivatedEvent;
use Cicada\Core\Framework\App\Event\AppDeletedEvent;
use Cicada\Core\Framework\App\Event\AppInstalledEvent;
use Cicada\Core\Framework\App\Event\AppUpdatedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\MessageQueue\IterateEntityIndexerMessage;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostInstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUninstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('after-sales')]
class FlowIndexerSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginPostInstallEvent::class => 'refreshPlugin',
            PluginPostActivateEvent::class => 'refreshPlugin',
            PluginPostUpdateEvent::class => 'refreshPlugin',
            PluginPostDeactivateEvent::class => 'refreshPlugin',
            PluginPostUninstallEvent::class => 'refreshPlugin',
            AppInstalledEvent::class => 'refreshPlugin',
            AppUpdatedEvent::class => 'refreshPlugin',
            AppActivatedEvent::class => 'refreshPlugin',
            AppDeletedEvent::class => 'refreshPlugin',
            AppDeactivatedEvent::class => 'refreshPlugin',
        ];
    }

    public function refreshPlugin(): void
    {
        // Schedule indexer to update flows
        $this->messageBus->dispatch(new IterateEntityIndexerMessage(FlowIndexer::NAME, null));
    }
}

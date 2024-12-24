<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache\Script;

use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class ScriptCacheInvalidationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ScriptExecutor $scriptExecutor)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'executeCacheInvalidationHook',
        ];
    }

    public function executeCacheInvalidationHook(EntityWrittenContainerEvent $event): void
    {
        $this->scriptExecutor->execute(
            new CacheInvalidationHook($event)
        );
    }
}

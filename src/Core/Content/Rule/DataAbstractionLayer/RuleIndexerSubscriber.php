<?php declare(strict_types=1);

namespace Cicada\Core\Content\Rule\DataAbstractionLayer;

use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Content\Rule\RuleEvents;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostInstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUninstallEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUpdateEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('services-settings')]
class RuleIndexerSubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly CartRuleLoader $cartRuleLoader
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginPostInstallEvent::class => 'refreshPlugin',
            PluginPostActivateEvent::class => 'refreshPlugin',
            PluginPostUpdateEvent::class => 'refreshPlugin',
            PluginPostDeactivateEvent::class => 'refreshPlugin',
            PluginPostUninstallEvent::class => 'refreshPlugin',
            RuleEvents::RULE_WRITTEN_EVENT => 'onRuleWritten',
        ];
    }

    public function refreshPlugin(): void
    {
        // Delete the payload and invalid flag of all rules
        $update = new RetryableQuery(
            $this->connection,
            $this->connection->prepare('UPDATE `rule` SET `payload` = null, `invalid` = 0')
        );
        $update->execute();
    }

    public function onRuleWritten(): void
    {
        $this->cartRuleLoader->invalidate();
    }
}

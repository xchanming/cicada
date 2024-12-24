<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Webhook\ScheduledTask;

use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Cicada\Core\Framework\Webhook\Service\WebhookCleanup;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: CleanupWebhookEventLogTask::class)]
#[Package('core')]
final class CleanupWebhookEventLogTaskHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $repository,
        LoggerInterface $logger,
        private readonly WebhookCleanup $webhookCleanup
    ) {
        parent::__construct($repository, $logger);
    }

    public function run(): void
    {
        $this->webhookCleanup->removeOldLogs();
    }
}

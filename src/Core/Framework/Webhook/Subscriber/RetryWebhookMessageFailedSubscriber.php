<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Webhook\Subscriber;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Webhook\EventLog\WebhookEventLogDefinition;
use Cicada\Core\Framework\Webhook\Message\WebhookEventMessage;
use Cicada\Core\Framework\Webhook\Service\RelatedWebhooks;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * @internal
 */
#[Package('core')]
class RetryWebhookMessageFailedSubscriber implements EventSubscriberInterface
{
    private const MAX_WEBHOOK_ERROR_COUNT = 10;

    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $webhookEventLogRepository,
        private readonly RelatedWebhooks $relatedWebhooks
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'failed',
        ];
    }

    public function failed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof WebhookEventMessage) {
            return;
        }

        $webhookId = $message->getWebhookId();
        $webhookEventLogId = $message->getWebhookEventId();

        $context = Context::createDefaultContext();

        $this->markWebhookEventFailed($webhookEventLogId, $context);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT active, error_count FROM webhook WHERE id = :id',
            ['id' => $webhookId]
        );

        /** @var array{active: int, error_count: int} $webhook */
        $webhook = current($rows);

        if (!\is_array($webhook) || !$webhook['active']) {
            return;
        }

        $webhookErrorCount = $webhook['error_count'] + 1;
        $params = ['error_count' => $webhookErrorCount];

        if ($webhookErrorCount >= self::MAX_WEBHOOK_ERROR_COUNT) {
            $params = array_merge($params, [
                'error_count' => 0,
                'active' => false,
            ]);
        }

        $this->relatedWebhooks->updateRelated($webhookId, $params, $context);
    }

    private function markWebhookEventFailed(string $id, Context $context): void
    {
        $this->webhookEventLogRepository->update([
            ['id' => $id, 'deliveryStatus' => WebhookEventLogDefinition::STATUS_FAILED],
        ], $context);
    }
}

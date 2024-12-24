<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Webhook\Subscriber;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\EventLog\WebhookEventLogCollection;
use Cicada\Core\Framework\Webhook\EventLog\WebhookEventLogDefinition;
use Cicada\Core\Framework\Webhook\Message\WebhookEventMessage;
use Cicada\Core\Framework\Webhook\Subscriber\RetryWebhookMessageFailedSubscriber;
use Cicada\Tests\Integration\Core\Framework\App\GuzzleTestClientBehaviour;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * @internal
 */
class RetryWebhookMessageFailedSubscriberTest extends TestCase
{
    use GuzzleTestClientBehaviour;
    use IntegrationTestBehaviour;

    private Context $context;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
    }

    public function testHandleWebhookMessageFailed(): void
    {
        $webhookId = Uuid::randomHex();
        $appId = Uuid::randomHex();
        $webhookEventId = Uuid::randomHex();

        $appRepository = static::getContainer()->get('app.repository');
        /** @var EntityRepository<WebhookEventLogCollection> $webhookEventLogRepository */
        $webhookEventLogRepository = static::getContainer()->get('webhook_event_log.repository');

        $appRepository->create([[
            'id' => $appId,
            'name' => 'SwagApp',
            'active' => true,
            'path' => __DIR__ . '/Manifest/_fixtures/test',
            'version' => '0.0.1',
            'label' => 'test',
            'appSecret' => 's3cr3t',
            'integration' => [
                'label' => 'test',
                'accessKey' => 'api access key',
                'secretAccessKey' => 'test',
            ],
            'aclRole' => [
                'name' => 'SwagApp',
            ],
            'webhooks' => [
                [
                    'id' => $webhookId,
                    'name' => 'hook1',
                    'eventName' => 'order',
                    'url' => 'https://test.com',
                ],
            ],
        ]], $this->context);

        $webhookEventMessage = new WebhookEventMessage($webhookEventId, ['body' => 'payload'], $appId, $webhookId, '6.4', 'http://test.com', 's3cr3t', Defaults::LANGUAGE_SYSTEM, 'en-GB');

        $webhookEventLogRepository->create([[
            'id' => $webhookEventId,
            'appName' => 'SwagApp',
            'deliveryStatus' => WebhookEventLogDefinition::STATUS_QUEUED,
            'webhookName' => 'hook1',
            'eventName' => 'order',
            'appVersion' => '0.0.1',
            'url' => 'https://test.com',
            'serializedWebhookMessage' => serialize($webhookEventMessage),
        ]], $this->context);

        $event = new WorkerMessageFailedEvent(
            new Envelope($webhookEventMessage),
            'async',
            new ClientException('test', new Request('GET', 'https://test.com'), new Response(500))
        );

        static::getContainer()->get(RetryWebhookMessageFailedSubscriber::class)
            ->failed($event);

        $webhookEventLog = $webhookEventLogRepository->search(new Criteria([$webhookEventId]), $this->context)
            ->getEntities()
            ->first();
        static::assertNotNull($webhookEventLog);
        static::assertEquals($webhookEventLog->getDeliveryStatus(), WebhookEventLogDefinition::STATUS_FAILED);
    }
}

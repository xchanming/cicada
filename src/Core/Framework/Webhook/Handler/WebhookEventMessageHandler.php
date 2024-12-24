<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Webhook\Handler;

use Cicada\Core\Framework\App\Exception\AppNotFoundException;
use Cicada\Core\Framework\App\Hmac\Guzzle\AuthMiddleware;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteTypeIntendException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Webhook\EventLog\WebhookEventLogDefinition;
use Cicada\Core\Framework\Webhook\Message\WebhookEventMessage;
use Cicada\Core\Framework\Webhook\Service\RelatedWebhooks;
use Cicada\Core\Framework\Webhook\WebhookException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
#[Package('core')]
final class WebhookEventMessageHandler
{
    private const TIMEOUT = 20;
    private const CONNECT_TIMEOUT = 10;

    /**
     * @internal
     */
    public function __construct(
        private readonly Client $client,
        private readonly EntityRepository $webhookEventLogRepository,
        private readonly RelatedWebhooks $relatedWebhooks,
    ) {
    }

    public function __invoke(WebhookEventMessage $message): void
    {
        $cicadaVersion = $message->getCicadaVersion();

        $payload = $message->getPayload();
        $url = $message->getUrl();

        $timestamp = time();
        $payload['timestamp'] = $timestamp;

        $jsonPayload = json_encode($payload, \JSON_THROW_ON_ERROR);

        $headers = ['Content-Type' => 'application/json',
            'sw-version' => $cicadaVersion, ];

        // LanguageId and UserLocale will be required from 6.5.0 onward
        if ($message->getLanguageId() && $message->getUserLocale()) {
            $headers = array_merge($headers, [AuthMiddleware::CICADA_CONTEXT_LANGUAGE => $message->getLanguageId(), AuthMiddleware::CICADA_USER_LANGUAGE => $message->getUserLocale()]);
        }

        $requestContent = [
            'headers' => $headers,
            'body' => $jsonPayload,
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'timeout' => self::TIMEOUT,
        ];

        if ($message->getSecret()) {
            $requestContent[AuthMiddleware::APP_REQUEST_TYPE] = [
                AuthMiddleware::APP_SECRET => $message->getSecret(),
            ];
        }

        $context = Context::createDefaultContext();

        $this->webhookEventLogRepository->update([
            [
                'id' => $message->getWebhookEventId(),
                'deliveryStatus' => WebhookEventLogDefinition::STATUS_RUNNING,
                'timestamp' => $timestamp,
                'requestContent' => $requestContent,
            ],
        ], $context);

        try {
            $response = $this->client->post($url, $requestContent);

            $this->webhookEventLogRepository->update([
                [
                    'id' => $message->getWebhookEventId(),
                    'deliveryStatus' => WebhookEventLogDefinition::STATUS_SUCCESS,
                    'processingTime' => time() - $timestamp,
                    'responseContent' => [
                        'headers' => $response->getHeaders(),
                        'body' => \json_decode($response->getBody()->getContents(), true),
                    ],
                    'responseStatusCode' => $response->getStatusCode(),
                    'responseReasonPhrase' => $response->getReasonPhrase(),
                ],
            ], $context);

            try {
                $this->relatedWebhooks->updateRelated($message->getWebhookId(), ['error_count' => 0], $context);
            } catch (AppNotFoundException|WriteTypeIntendException $e) {
                // may happen if app or webhook got deleted in the meantime,
                // we don't need to update the error-count in that case, so we can ignore the error
            }
        } catch (\Throwable $e) {
            $payload = [
                'id' => $message->getWebhookEventId(),
                'deliveryStatus' => WebhookEventLogDefinition::STATUS_QUEUED, // we use the message retry mechanism to retry the message here so we set the status to queued, because it will be automatically executed again.
                'processingTime' => time() - $timestamp,
            ];

            if ($e instanceof RequestException && $e->getResponse() !== null) {
                $response = $e->getResponse();
                $body = $response->getBody()->getContents();
                if (json_validate($body)) {
                    $body = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
                }
                $payload = array_merge($payload, [
                    'responseContent' => [
                        'headers' => $response->getHeaders(),
                        'body' => $body,
                    ],
                    'responseStatusCode' => $response->getStatusCode(),
                    'responseReasonPhrase' => $response->getReasonPhrase(),
                ]);
            }

            $this->webhookEventLogRepository->update([$payload], $context);

            if ($e instanceof BadResponseException && $message->getAppId()) {
                throw WebhookException::appWebhookFailedException($message->getWebhookId(), $message->getAppId(), $e);
            }

            throw WebhookException::webhookFailedException($message->getWebhookId(), $e);
        }
    }
}

<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Webhook;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Webhook\WebhookException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(WebhookException::class)]
class WebhookExceptionTest extends TestCase
{
    public function testAppWebhookFailedException(): void
    {
        $e = WebhookException::appWebhookFailedException('webhookId', 'appId', new \Exception('error'));
        static::assertEquals('Webhook "webhookId" from "appId" failed with error: error.', $e->getMessage());
        static::assertEquals('FRAMEWORK__APP_WEBHOOK_FAILED', $e->getErrorCode());
        static::assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getStatusCode());
    }

    public function testWebhookFailedException(): void
    {
        $e = WebhookException::webhookFailedException('webhookId', new \Exception('error'));
        static::assertEquals('Webhook "webhookId" failed with error: error.', $e->getMessage());
        static::assertEquals('FRAMEWORK__WEBHOOK_FAILED', $e->getErrorCode());
        static::assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getStatusCode());
    }
}

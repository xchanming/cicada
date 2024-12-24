<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Webhook\ScheduledTask;

use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Webhook\ScheduledTask\CleanupWebhookEventLogTaskHandler;
use Cicada\Core\Framework\Webhook\Service\WebhookCleanup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(CleanupWebhookEventLogTaskHandler::class)]
class CleanupWebhookEventLogTaskHandlerTest extends TestCase
{
    public function testHandler(): void
    {
        $cleaner = $this->createMock(WebhookCleanup::class);

        $cleaner->expects(static::once())->method('removeOldLogs');

        $handler = new CleanupWebhookEventLogTaskHandler(
            $this->createMock(EntityRepository::class),
            $this->createMock(LoggerInterface::class),
            $cleaner
        );

        $handler->run();
    }
}

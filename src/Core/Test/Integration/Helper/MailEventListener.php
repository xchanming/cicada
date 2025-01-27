<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\Helper;

use Cicada\Core\Content\Flow\Events\FlowSendMailActionEvent;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MailEventListener
{
    private array $events = [];

    public function __construct(private readonly array $mapping)
    {
    }

    public function __invoke(FlowSendMailActionEvent $event): void
    {
        $name = $this->mapping[$event->getMailTemplate()->getMailTemplateTypeId()];

        $this->events[$name][] = $event;
    }

    public function assertSent(string $type): void
    {
        TestCase::assertTrue($this->sent($type), \sprintf('Expected to send %s mail', $type));
    }

    public function sent(string $type): bool
    {
        return !empty($this->events[$type]);
    }

    public function get(?string $type = null): array
    {
        return $type ? $this->events[$type] : $this->events;
    }
}

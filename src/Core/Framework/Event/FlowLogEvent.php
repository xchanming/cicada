<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('services-settings')]
class FlowLogEvent extends Event implements FlowEventAware
{
    final public const NAME = 'flow.log';

    /**
     * @var array<string, mixed>
     */
    private readonly array $config;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(
        private readonly string $name,
        private readonly FlowEventAware $event,
        ?array $config = []
    ) {
        $this->config = $config ?? [];
    }

    public static function getAvailableData(): EventDataCollection
    {
        return new EventDataCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEvent(): FlowEventAware
    {
        return $this->event;
    }

    public function getContext(): Context
    {
        return $this->event->getContext();
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}

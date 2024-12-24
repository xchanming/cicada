<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Event;

use Cicada\Core\Content\Flow\Dispatching\Aware\CustomAppAware;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class CustomAppEvent extends Event implements CustomAppAware, FlowEventAware
{
    /**
     * @param array<string, mixed>|null $appData
     */
    public function __construct(
        private readonly string $name,
        private readonly ?array $appData,
        private readonly Context $context
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCustomAppData(): ?array
    {
        return $this->appData;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return new EventDataCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}

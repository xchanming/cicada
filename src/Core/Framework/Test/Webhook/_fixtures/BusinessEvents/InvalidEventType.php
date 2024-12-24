<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents;

use Cicada\Core\Framework\Event\EventData\EventDataType;

/**
 * @internal
 */
class InvalidEventType implements EventDataType
{
    public function toArray(): array
    {
        return [
            'type' => 'invalid',
        ];
    }
}

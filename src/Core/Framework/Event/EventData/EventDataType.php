<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event\EventData;

use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
interface EventDataType
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
interface FlowEventAware extends CicadaEvent
{
    public static function getAvailableData(): EventDataCollection;

    public function getName(): string;
}

<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Log;

use Monolog\Level;
use Cicada\Core\Framework\Event\IsFlowEventAware;

#[IsFlowEventAware]
#[Package('core')]
interface LogAware
{
    /**
     * @return array<string, mixed>
     */
    public function getLogData(): array;

    public function getLogLevel(): Level;
}

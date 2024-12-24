<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Log;

use Cicada\Core\Framework\Event\IsFlowEventAware;
use Monolog\Level;

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

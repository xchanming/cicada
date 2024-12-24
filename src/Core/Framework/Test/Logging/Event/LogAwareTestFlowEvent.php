<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Logging\Event;

use Cicada\Core\Content\Test\Flow\TestFlowBusinessEvent;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\LogAware;
use Monolog\Level;

/**
 * @internal
 */
class LogAwareTestFlowEvent extends TestFlowBusinessEvent implements LogAware, FlowEventAware
{
    final public const EVENT_NAME = 'test.flow_event.log_aware';

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getLogData(): array
    {
        return ['awesomekey' => 'awesomevalue'];
    }

    public function getLogLevel(): Level
    {
        return Level::Emergency;
    }
}

<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules\data\UseCLIContextRule;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

/**
 * @internal
 */
final class TaskHandler extends ScheduledTaskHandler
{
    public function run(): void
    {
        Context::createDefaultContext();
    }
}

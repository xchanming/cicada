<?php declare(strict_types=1);

namespace Cicada\Core\Framework\MessageQueue\ScheduledTask\MessageQueue;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Registry\TaskRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @final
 *
 * @internal
 */
#[AsMessageHandler]
#[Package('core')]
class RegisterScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(private readonly TaskRegistry $registry)
    {
    }

    public function __invoke(RegisterScheduledTaskMessage $message): void
    {
        $this->registry->registerTasks();
    }
}

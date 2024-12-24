<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\ScheduledTask;

use Cicada\Core\Framework\App\Lifecycle\Update\AbstractAppUpdater;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: UpdateAppsTask::class)]
#[Package('core')]
final class UpdateAppsHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly AbstractAppUpdater $appUpdater
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $this->appUpdater->updateApps(Context::createCLIContext());
    }
}

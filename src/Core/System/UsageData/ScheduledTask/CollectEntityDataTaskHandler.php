<?php declare(strict_types=1);

namespace Cicada\Core\System\UsageData\ScheduledTask;

use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Cicada\Core\System\UsageData\Services\EntityDispatchService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('data-services')]
#[AsMessageHandler(handles: CollectEntityDataTask::class)]
final class CollectEntityDataTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $repository,
        LoggerInterface $logger,
        private readonly EntityDispatchService $entityDispatchService,
    ) {
        parent::__construct($repository, $logger);
    }

    public function run(): void
    {
        $this->entityDispatchService->dispatchCollectEntityDataMessage();
    }
}

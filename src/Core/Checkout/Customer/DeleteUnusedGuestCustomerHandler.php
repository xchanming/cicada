<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: DeleteUnusedGuestCustomerTask::class)]
#[Package('checkout')]
final class DeleteUnusedGuestCustomerHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly DeleteUnusedGuestCustomerService $unusedGuestCustomerService
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $this->unusedGuestCustomerService->deleteUnusedCustomers(Context::createCLIContext());
    }
}

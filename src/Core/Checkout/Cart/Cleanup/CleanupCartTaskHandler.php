<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Cleanup;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 *  @internal
 */
#[AsMessageHandler(handles: CleanupCartTask::class)]
#[Package('checkout')]
final class CleanupCartTaskHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $repository,
        LoggerInterface $logger,
        private readonly AbstractCartPersister $cartPersister,
        private readonly int $days
    ) {
        parent::__construct($repository, $logger);
    }

    public function run(): void
    {
        $this->cartPersister->prune($this->days);
    }
}

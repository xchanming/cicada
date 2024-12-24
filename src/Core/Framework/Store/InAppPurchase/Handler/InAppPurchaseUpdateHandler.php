<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\InAppPurchase\Handler;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Cicada\Core\Framework\Store\InAppPurchase\InAppPurchaseUpdateTask;
use Cicada\Core\Framework\Store\InAppPurchase\Services\InAppPurchaseUpdater;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[AsMessageHandler(handles: InAppPurchaseUpdateTask::class)]
#[Package('checkout')]
final class InAppPurchaseUpdateHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly InAppPurchaseUpdater $iapUpdater
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $context = Context::createCLIContext();
        $this->iapUpdater->update($context);
    }
}

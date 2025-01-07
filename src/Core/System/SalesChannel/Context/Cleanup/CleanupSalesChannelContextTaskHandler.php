<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Context\Cleanup;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: CleanupSalesChannelContextTask::class)]
#[Package('discovery')]
final class CleanupSalesChannelContextTaskHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $repository,
        LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly int $days
    ) {
        parent::__construct($repository, $logger);
    }

    public function run(): void
    {
        $time = new \DateTime();
        $time->modify(\sprintf('-%d day', $this->days));

        $this->connection->executeStatement(
            'DELETE FROM sales_channel_api_context WHERE updated_at <= :timestamp',
            ['timestamp' => $time->format(Defaults::STORAGE_DATE_TIME_FORMAT)]
        );
    }
}

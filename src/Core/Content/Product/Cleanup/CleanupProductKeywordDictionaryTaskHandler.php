<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Cleanup;

use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler(handles: CleanupProductKeywordDictionaryTask::class)]
#[Package('inventory')]
final class CleanupProductKeywordDictionaryTaskHandler extends ScheduledTaskHandler
{
    /**
     * @internal
     */
    public function __construct(
        EntityRepository $repository,
        LoggerInterface $logger,
        private readonly Connection $connection
    ) {
        parent::__construct($repository, $logger);
    }

    public function run(): void
    {
        do {
            $result = RetryableQuery::retryable(
                $this->connection,
                fn (): int => (int) $this->connection->executeStatement(
                    'DELETE FROM product_keyword_dictionary WHERE keyword NOT IN (SELECT DISTINCT keyword FROM product_search_keyword) LIMIT 1000',
                )
            );
        } while ($result > 0);
    }
}

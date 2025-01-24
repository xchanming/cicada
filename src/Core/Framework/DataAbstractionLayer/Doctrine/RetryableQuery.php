<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Doctrine;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\MeterProvider;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Statement;

#[Package('framework')]
class RetryableQuery
{
    public function __construct(
        private readonly ?Connection $connection,
        private readonly Statement $query
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function execute(array $params = []): int
    {
        return self::retry($this->connection, fn () => $this->query->executeStatement($params), 0);
    }

    /**
     * @template TReturn of mixed
     *
     * @param \Closure(): TReturn $closure
     *
     * @return TReturn
     */
    public static function retryable(Connection $connection, \Closure $closure)
    {
        return self::retry($connection, $closure, 0);
    }

    public function getQuery(): Statement
    {
        return $this->query;
    }

    /**
     * @template TReturn of mixed
     *
     * @param \Closure(): TReturn $closure
     *
     * @return TReturn
     */
    private static function retry(?Connection $connection, \Closure $closure, int $counter)
    {
        ++$counter;

        try {
            return $closure();
        } catch (RetryableException $e) {
            MeterProvider::meter()?->emit(new ConfiguredMetric('database.locks.count', 1));
            if ($connection && $connection->getTransactionNestingLevel() > 0) {
                // If this closure was executed inside a transaction, do not retry. Remember that the whole (outermost)
                // transaction was already rolled back by the database when any RetryableException is thrown. Rethrow
                // the exception here so only the outermost transaction is retried which in turn includes this closure.
                throw $e;
            }

            if ($counter > 10) {
                throw $e;
            }

            // randomize sleep to prevent same execution delay for multiple statements
            usleep(20 * $counter);

            return self::retry($connection, $closure, $counter);
        }
    }
}

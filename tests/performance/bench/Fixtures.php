<?php declare(strict_types=1);

namespace Cicada\Tests\Bench;

use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\FixtureLoader;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;

/**
 * @internal - only for performance benchmarks
 */
class Fixtures
{
    private static ?IdsCollection $ids = null;

    private static ?SalesChannelContext $context = null;

    public function load(string $data): void
    {
        $content = $data;
        if (is_file($data)) {
            $content = (string) \file_get_contents($data);
        }
        $container = KernelLifecycleManager::getKernel()->getContainer();
        $loader = new FixtureLoader($container);
        $ids = $loader->load($content, self::$ids);

        $sql = '
CREATE TABLE IF NOT EXISTS `php_bench` (
  `key` varchar(50) NOT NULL,
  `ids` longblob NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ';

        $container
            ->get(Connection::class)
            ->executeStatement($sql);

        $sql = 'REPLACE INTO php_bench (`key`, `ids`) VALUES (:key, :ids)';

        $container
            ->get(Connection::class)
            ->executeStatement($sql, ['key' => 'ids', 'ids' => \serialize($ids)]);
    }

    public static function getIds(): IdsCollection
    {
        if (!self::$ids instanceof IdsCollection) {
            $ids = KernelLifecycleManager::getKernel()
                ->getContainer()
                ->get(Connection::class)
                ->fetchOne('SELECT ids FROM php_bench WHERE `key` = :key', ['key' => 'ids']);

            self::$ids = \unserialize($ids);
        }

        return self::$ids;
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function context(array $options = []): SalesChannelContext
    {
        if (!self::$context instanceof SalesChannelContext) {
            self::$context = KernelLifecycleManager::getKernel()
                ->getContainer()
                ->get(SalesChannelContextFactory::class)
                ->create(Uuid::randomHex(), self::getIds()->get('sales-channel'), $options);
        }

        return self::$context;
    }
}

<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Migration\V6_6\Migration1723193659UpdateVatPatternForCountry;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(Migration1723193659UpdateVatPatternForCountry::class)]
class Migration1723193659UpdateVatPatternForCountryTest extends TestCase
{
    private const OLD_PATTERNS = [
        'CN' => '^[0-9A-Z]{18}$',
    ];

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();

        $this->prepare($this->connection);
    }

    public function testMigration(): void
    {
        $patterns = $this->connection->fetchAllKeyValue(
            'SELECT iso, vat_id_pattern FROM country WHERE iso IN (:iso)',
            ['iso' => array_keys(self::OLD_PATTERNS)],
            ['iso' => ArrayParameterType::STRING]
        );

        foreach (self::OLD_PATTERNS as $key => $pattern) {
            static::assertSame($pattern, $patterns[$key]);
        }

        $migration = new Migration1723193659UpdateVatPatternForCountry();
        $migration->update($this->connection);

        $patterns = $this->connection->fetchAllKeyValue(
            'SELECT iso, vat_id_pattern FROM country WHERE iso IN (:iso)',
            ['iso' => array_keys(self::OLD_PATTERNS)],
            ['iso' => ArrayParameterType::STRING]
        );

        static::assertSame('^[0-9A-Z]{18}$', $patterns['CN']);
    }

    private function prepare(Connection $connection): void
    {
        $update = new RetryableQuery(
            $connection,
            $connection->prepare('UPDATE country SET vat_id_pattern = :pattern WHERE iso = :iso')
        );

        foreach (self::OLD_PATTERNS as $key => $pattern) {
            $update->execute([
                'pattern' => $pattern,
                'iso' => $key,
            ]);
        }
    }
}

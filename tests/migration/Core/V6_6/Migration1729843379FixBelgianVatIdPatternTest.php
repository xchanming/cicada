<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Migration\V6_6\Migration1729843379FixBelgianVatIdPattern;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(Migration1729843379FixBelgianVatIdPattern::class)]
class Migration1729843379FixBelgianVatIdPatternTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testMigrate(): void
    {
        $this->connection->update('country', ['vat_id_pattern' => 'BE0\d{9}'], ['iso' => 'BE']);

        $migration = new Migration1729843379FixBelgianVatIdPattern();
        $migration->update($this->connection);
        $migration->update($this->connection);

        $result = $this->connection
            ->executeQuery('SELECT `vat_id_pattern` FROM country WHERE vat_id_pattern = :pat', ['pat' => 'BE(0|1)\d{9}'])
            ->fetchAssociative();

        static::assertNotEmpty($result);
        static::assertSame('BE(0|1)\d{9}', $result['vat_id_pattern']);
    }
}

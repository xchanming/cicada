<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Migration\V6_6\Migration1697788982ChangeColumnAvailabilityRuleIdFromShippingMethodToNullable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Migration1697788982ChangeColumnAvailabilityRuleIdFromShippingMethodToNullable::class)]
class Migration1697788982ChangeColumnAvailabilityRuleIdFromShippingMethodToNullableTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
    }

    /**
     * @throws Exception
     */
    public function testMigration(): void
    {
        $migration = new Migration1697788982ChangeColumnAvailabilityRuleIdFromShippingMethodToNullable();
        static::assertSame(1697788982, $migration->getCreationTimestamp());

        $migration->update($this->connection);

        $columns = $this->connection->fetchAllAssociativeIndexed('SHOW COLUMNS FROM `shipping_method`');
        static::assertSame('YES', $columns['availability_rule_id']['Null']);
        static::assertNull($columns['availability_rule_id']['Default']);
    }
}

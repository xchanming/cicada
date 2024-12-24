<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_7;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\V6_7\Migration1720610755RemoveDefaultPaymentMethodFromCustomer;

/**
 * @internal
 */
#[CoversClass(Migration1720610755RemoveDefaultPaymentMethodFromCustomer::class)]
class Migration1720610755RemoveDefaultPaymentMethodFromCustomerTest extends TestCase
{
    use BasicTestDataBehaviour;
    use KernelTestBehaviour;

    public function testUpdateMakesColumnNullable(): void
    {
        if (!$this->columnExists()) {
            $this->addColumn();
        }

        $migration = new Migration1720610755RemoveDefaultPaymentMethodFromCustomer();
        $migration->update(static::getContainer()->get(Connection::class));
        $migration->update(static::getContainer()->get(Connection::class));

        $column = static::getContainer()->get(Connection::class)->fetchAssociative(
            'SHOW COLUMNS FROM `customer` WHERE `Field` LIKE "default_payment_method_id"',
        ) ?: [];
        static::assertArrayHasKey('Null', $column);
        static::assertSame('YES', $column['Null']);
    }

    public function testUpdateDestructiveRemovesColumn(): void
    {
        $exists = $this->columnExists();

        if (!$exists) {
            $this->addColumn();
        }

        $migration = new Migration1720610755RemoveDefaultPaymentMethodFromCustomer();
        $migration->updateDestructive(static::getContainer()->get(Connection::class));
        $migration->updateDestructive(static::getContainer()->get(Connection::class));

        static::assertFalse($this->columnExists());

        if ($exists) {
            $this->addColumn();
        }
    }

    private function addColumn(): void
    {
        static::getContainer()->get(Connection::class)
            ->executeStatement(
                'ALTER TABLE `customer`
                    ADD COLUMN `default_payment_method_id` BINARY(16) NOT NULL DEFAULT :defaultPaymentMethodId,
                    ADD CONSTRAINT `fk.customer.default_payment_method_id` FOREIGN KEY (`default_payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
                ['defaultPaymentMethodId' => Uuid::fromHexToBytes($this->getValidPaymentMethodId())]
            );
    }

    private function columnExists(): bool
    {
        return (bool) static::getContainer()->get(Connection::class)->fetchOne(
            'SHOW COLUMNS FROM `customer` WHERE `Field` LIKE "default_payment_method_id"',
        );
    }
}

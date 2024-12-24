<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_7;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Migration\V6_7\Migration1697112044PaymentAndShippingTechnicalNameRequired;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Migration1697112044PaymentAndShippingTechnicalNameRequired::class)]
class Migration1697112044PaymentAndShippingTechnicalNameRequiredTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        Feature::skipTestIfInActive('v6.7.0.0', $this);

        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testGetCreationTimestamp(): void
    {
        static::assertSame(1697112044, (new Migration1697112044PaymentAndShippingTechnicalNameRequired())->getCreationTimestamp());
    }

    public function testMigrate(): void
    {
        $this->rollback();
        $this->migrate();
        $this->migrate();

        $manager = $this->connection->createSchemaManager();
        $columns = $manager->listTableColumns(PaymentMethodDefinition::ENTITY_NAME);

        static::assertArrayHasKey('technical_name', $columns);
        static::assertTrue($columns['technical_name']->getNotnull());

        $columns = $manager->listTableColumns(ShippingMethodDefinition::ENTITY_NAME);

        static::assertArrayHasKey('technical_name', $columns);
        static::assertTrue($columns['technical_name']->getNotnull());
    }

    private function migrate(): void
    {
        (new Migration1697112044PaymentAndShippingTechnicalNameRequired())->update($this->connection);
    }

    private function rollback(): void
    {
        $this->connection->executeStatement('ALTER TABLE `payment_method` MODIFY COLUMN `technical_name` VARCHAR(255) NULL');
        $this->connection->executeStatement('ALTER TABLE `shipping_method` MODIFY COLUMN `technical_name` VARCHAR(255) NULL');
    }
}

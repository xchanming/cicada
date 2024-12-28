<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1697112044PaymentAndShippingTechnicalNameRequired extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1697112044;
    }

    public function update(Connection $connection): void
    {
        $manager = $connection->createSchemaManager();
        $columns = $manager->listTableColumns(PaymentMethodDefinition::ENTITY_NAME);

        if (\array_key_exists('technical_name', $columns) && !$columns['technical_name']->getNotnull()) {
            $connection->executeStatement('ALTER TABLE `payment_method` MODIFY COLUMN `technical_name` VARCHAR(255) NOT NULL');
        }

        $columns = $manager->listTableColumns('shipping_method');

        if (\array_key_exists('technical_name', $columns) && !$columns['technical_name']->getNotnull()) {
            $connection->executeStatement('ALTER TABLE `shipping_method` MODIFY COLUMN `technical_name` VARCHAR(255) NOT NULL');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

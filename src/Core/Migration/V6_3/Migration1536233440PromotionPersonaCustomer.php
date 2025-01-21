<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1536233440PromotionPersonaCustomer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233440;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `promotion_persona_customer` (
                promotion_id BINARY(16) NOT NULL,
                customer_id BINARY(16) NOT NULL,
                PRIMARY KEY (`promotion_id`, `customer_id`),
                CONSTRAINT `fk.promotion_persona_customer.promotion_id` FOREIGN KEY (promotion_id)
                  REFERENCES promotion (id) ON DELETE CASCADE,
                CONSTRAINT `fk.promotion_persona_customer.customer_id` FOREIGN KEY (customer_id)
                  REFERENCES customer (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
       ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

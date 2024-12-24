<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1673420896RemoveUndefinedSalutation extends MigrationStep
{
    private const ASSOCIATION_TABLES = [
        'customer_address',
        'customer',
        'order_customer',
        'order_address',
        'newsletter_recipient',
    ];

    public function getCreationTimestamp(): int
    {
        return 1673420896;
    }

    public function update(Connection $connection): void
    {
        foreach (self::ASSOCIATION_TABLES as $table) {
            $fkName = 'fk.' . $table . '.salutation_id';

            if (!$this->indexExists($connection, $table, $fkName)) {
                continue;
            }

            // Drop FK constraints to change from restrict delete to set null on delete
            $this->dropForeignKeyIfExists($connection, $table, $fkName);
            $connection->executeStatement('ALTER TABLE `' . $table . '` ADD CONSTRAINT `' . $fkName . '` FOREIGN KEY (`salutation_id`) REFERENCES `salutation` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        $undefinedSalutationId = $connection->fetchOne('SELECT `id` FROM `salutation` WHERE `salutation_key` = "undefined"');

        if (!$undefinedSalutationId) {
            return;
        }

        $connection->executeStatement('DELETE FROM `salutation` WHERE `id` = :id', ['id' => $undefinedSalutationId]);
    }
}

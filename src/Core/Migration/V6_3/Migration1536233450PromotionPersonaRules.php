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
class Migration1536233450PromotionPersonaRules extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233450;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `promotion_persona_rule` (
                `promotion_id` BINARY(16) NOT NULL,
                `rule_id` BINARY(16) NOT NULL,
                PRIMARY KEY (`promotion_id`, `rule_id`),
                CONSTRAINT `fk.promotion_persona_rule.promotion_id` FOREIGN KEY (`promotion_id`)
                  REFERENCES `promotion` (id) ON DELETE CASCADE,
                CONSTRAINT `fk.promotion_persona_rule.rule_id` FOREIGN KEY (`rule_id`)
                  REFERENCES `rule` (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
       ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

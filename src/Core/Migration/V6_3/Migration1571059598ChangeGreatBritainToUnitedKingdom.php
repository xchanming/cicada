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
#[Package('core')]
class Migration1571059598ChangeGreatBritainToUnitedKingdom extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1571059598;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            UPDATE `country_translation`
            SET `name` = "United Kingdom"
            WHERE `name` = "Great Britain" AND (
                SELECT `locale`.`code`
                FROM `language`
                INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id`
                WHERE `language`.`id` = `country_translation`.`language_id`
            ) = "en-GB"
        ');

        $connection->executeStatement('
            UPDATE `country_translation`
            SET `name` = "Vereinigtes Königreich"
            WHERE `name` = "Großbritannien" AND (
                SELECT `locale`.`code`
                FROM `language`
                INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id`
                WHERE `language`.`id` = `country_translation`.`language_id`
            ) = "zh-CN"
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

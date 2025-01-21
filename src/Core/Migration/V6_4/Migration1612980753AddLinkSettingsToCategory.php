<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1612980753AddLinkSettingsToCategory extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1612980753;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
ALTER TABLE `category_translation`
    ADD COLUMN `link_type`      VARCHAR(255)   NULL AFTER `breadcrumb`,
    ADD COLUMN `link_new_tab`   TINYINT        NULL AFTER `breadcrumb`,
    ADD COLUMN `internal_link`  BINARY(16)     NULL AFTER `breadcrumb`
SQL;
        $connection->executeStatement($sql);

        $connection->createQueryBuilder()
            ->update('category_translation', 'translation')
            ->set('link_type', ':linkType')
            ->where('translation.external_link IS NOT NULL')
            ->orWhere('translation.category_id IN (SELECT id FROM category WHERE translation.category_id = category.id AND category.type = \'link\')')
            ->setParameter('linkType', 'external')
            ->executeStatement();
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

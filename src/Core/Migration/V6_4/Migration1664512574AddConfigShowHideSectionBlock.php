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
class Migration1664512574AddConfigShowHideSectionBlock extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1664512574;
    }

    public function update(Connection $connection): void
    {
        $this->updateSchema($connection, 'cms_section');
        $this->updateSchema($connection, 'cms_block');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function updateSchema(Connection $connection, string $tableName): void
    {
        if (!$this->columnExists($connection, $tableName, 'visibility')) {
            $connection->executeStatement(\sprintf('ALTER TABLE `%s` ADD COLUMN `visibility` JSON NULL AFTER `background_media_mode`', $tableName));
        }
    }
}

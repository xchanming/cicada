<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1730059142AddNewSitemapConfigForExcludingHiddenProducts extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1730059142;
    }

    public function update(Connection $connection): void
    {
        $config = $connection->fetchOne(
            'SELECT id FROM system_config WHERE configuration_key = \'core.sitemap.excludeLinkedProducts\''
        );

        if ($config !== false) {
            return;
        }

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => 'core.sitemap.excludeLinkedProducts',
            'configuration_value' => '{"_value": false}',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }
}

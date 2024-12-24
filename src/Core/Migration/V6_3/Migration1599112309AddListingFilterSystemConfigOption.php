<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1599112309AddListingFilterSystemConfigOption extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1599112309;
    }

    public function update(Connection $connection): void
    {
        $value = $this->isInstallation() ? 'true' : 'false';

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => 'core.listing.disableEmptyFilterOptions',
            'configuration_value' => '{"_value": ' . $value . '}',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

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
#[Package('core')]
class Migration1618389817RemoveTaxFreeFromColumnInCountryTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1618389817;
    }

    public function update(Connection $connection): void
    {
        $this->dropColumnIfExists($connection, 'country', 'tax_free_from');
    }
}

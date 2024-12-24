<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1716285861AddAppSourceType extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1716285861;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn($connection, 'app', 'source_type', 'VARCHAR(20)', false, '\'local\'');
    }
}

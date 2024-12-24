<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1661505878ChangeDefaultValueOfShippingMethodActiveField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1661505878;
    }

    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE shipping_method ALTER `active` SET DEFAULT 0;';

        $connection->executeStatement($sql);
    }
}

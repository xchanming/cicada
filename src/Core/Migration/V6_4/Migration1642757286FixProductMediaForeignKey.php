<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1642757286FixProductMediaForeignKey extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642757286;
    }

    public function update(Connection $connection): void
    {
        $this->dropForeignKeyIfExists($connection, 'product', 'fk.product.product_media_id');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

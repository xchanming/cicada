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
class Migration1578648299ReindexSeoUrls extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1578648299;
    }

    public function update(Connection $connection): void
    {
        $this->registerIndexer($connection, 'Swag.SeoUrlIndexer');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

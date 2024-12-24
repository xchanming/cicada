<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1594650256AddMailTemplateSalesChannelPK extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1594650256;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('
                ALTER TABLE `mail_template_sales_channel`
                ADD PRIMARY KEY (`id`);
            ');
        } catch (Exception) {
            // PK already exists
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }
}

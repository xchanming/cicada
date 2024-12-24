<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1696515133AddCheckoutGatewayUrl extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1696515133;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn($connection, 'app', 'checkout_gateway_url', 'VARCHAR(255) NULL');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

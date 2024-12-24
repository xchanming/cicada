<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1721811224AddInAppPurchaseGatewayUrl extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1721811224;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn($connection, 'app', 'in_app_purchases_gateway_url', 'VARCHAR(255)');
    }
}

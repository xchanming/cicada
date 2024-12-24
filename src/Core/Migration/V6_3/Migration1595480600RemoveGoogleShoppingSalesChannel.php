<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1595480600RemoveGoogleShoppingSalesChannel extends MigrationStep
{
    final public const SALES_CHANNEL_TYPE_GOOGLE_SHOPPING = 'eda0a7980ee745fbbb7e58202dcdc04f';

    public function getCreationTimestamp(): int
    {
        return 1595480600;
    }

    public function update(Connection $connection): void
    {
        $googleShopping = Uuid::fromHexToBytes(self::SALES_CHANNEL_TYPE_GOOGLE_SHOPPING);

        $connection->delete('sales_channel_type_translation', [
            'sales_channel_type_id' => $googleShopping,
        ]);

        $connection->delete('sales_channel_type', [
            'id' => $googleShopping,
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

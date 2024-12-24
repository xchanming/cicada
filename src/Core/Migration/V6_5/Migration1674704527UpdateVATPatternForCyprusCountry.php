<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1674704527UpdateVATPatternForCyprusCountry extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1674704527;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'UPDATE country SET vat_id_pattern = :pattern WHERE iso = :iso;',
            ['pattern' => '(CY)?[0-9]{8}[A-Z]{1}', 'iso' => 'CY']
        );
    }
}

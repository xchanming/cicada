<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1635230747UpdateProductExportTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1635230747;
    }

    public function update(Connection $connection): void
    {
        $templates = require __DIR__ . '/../Fixtures/productComparison-export-profiles/templates.php';

        $connection->update('product_export', ['body_template' => $templates['idealo_new'],   'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)], ['body_template' => $templates['idealo_old']]);
        $connection->update('product_export', ['body_template' => $templates['billiger_new'], 'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)], ['body_template' => $templates['billiger_old']]);
        $connection->update('product_export', ['body_template' => $templates['google_new'],   'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)], ['body_template' => $templates['google_old']]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

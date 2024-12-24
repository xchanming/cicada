<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1726557614FixProductComparisonIdealoWithHtmlFormat extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1726557614;
    }

    public function update(Connection $connection): void
    {
        $old_template = file_get_contents(__DIR__ . '/../Fixtures/productComparison-export-profiles/next-37658/old-template-idealo.csv.twig');
        $new_template = file_get_contents(__DIR__ . '/../Fixtures/productComparison-export-profiles/next-37658/new-template-idealo.csv.twig');

        $connection->update(
            'product_export',
            ['body_template' => $new_template, 'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
            ['body_template' => $old_template]
        );
    }
}

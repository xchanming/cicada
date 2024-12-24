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
class Migration1605103533AddCookiesToAppEntity extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1605103533;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::cookiesColumn());
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private static function cookiesColumn(): string
    {
        return <<<'EOF'
ALTER TABLE `app`
    ADD COLUMN `cookies` JSON NULL AFTER `modules`,
    ADD CONSTRAINT `json.app.cookies` CHECK (JSON_VALID(`cookies`));
EOF;
    }
}

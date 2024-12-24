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
class Migration1605530777PrivacyPolicyExtensionsToAppTranslation extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1605530777;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `app_translation`
            ADD `privacy_policy_extensions` MEDIUMTEXT NULL AFTER `description`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

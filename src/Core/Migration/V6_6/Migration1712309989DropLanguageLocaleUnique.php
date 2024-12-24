<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1712309989DropLanguageLocaleUnique extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1712309989;
    }

    public function update(Connection $connection): void
    {
        $this->dropIndexIfExists($connection, 'language', 'uniq.translation_code_id');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

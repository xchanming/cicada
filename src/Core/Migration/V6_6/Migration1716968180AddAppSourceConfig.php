<?php

declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1716968180AddAppSourceConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1716968180;
    }

    public function update(Connection $connection): void
    {
        $this->addColumn($connection, 'app', 'source_config', 'JSON', false, '(JSON_OBJECT())');
    }
}

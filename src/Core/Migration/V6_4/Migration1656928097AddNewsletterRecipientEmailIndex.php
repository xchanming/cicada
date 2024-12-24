<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1656928097AddNewsletterRecipientEmailIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1656928097;
    }

    public function update(Connection $connection): void
    {
        $existingIndexes = $connection->createSchemaManager()->listTableIndexes('newsletter_recipient');
        if (isset($existingIndexes['idx.newsletter_recipient.email'])) {
            return;
        }

        $connection->executeStatement('CREATE INDEX `idx.newsletter_recipient.email` ON `newsletter_recipient` (`email`)');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

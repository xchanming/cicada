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
class Migration1585056571AddLanguageToMailTemplateMedia extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1585056571;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            <<<'SQL'
            ALTER TABLE `mail_template_media` ADD `language_id` BINARY(16) NULL AFTER `mail_template_id`,
            ADD CONSTRAINT `fk.mail_template_media.language_id` FOREIGN KEY (`language_id`)
             REFERENCES `language` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
SQL
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

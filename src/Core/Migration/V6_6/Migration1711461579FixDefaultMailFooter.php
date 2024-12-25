<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\Traits\ImportTranslationsTrait;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1711461579FixDefaultMailFooter extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1711461579;
    }

    public function update(Connection $connection): void
    {
        $languages = $this->getLanguageIds($connection, 'zh-CN');
        if (!$languages) {
            return;
        }

        $connection->executeStatement(
            'UPDATE mail_header_footer_translation
            SET footer_plain = REPLACE(footer_plain, \'Addresse:\', \'Adresse:\')
            WHERE language_id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($languages)],
            ['ids' => ArrayParameterType::BINARY]
        );
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1587039363AddImportExportLabelField extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1587039363;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `import_export_profile` MODIFY `name` VARCHAR(255) NULL;');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `import_export_profile_translation` (
                `import_export_profile_id` BINARY(16) NOT NULL,
                `language_id` BINARY(16) NOT NULL,
                `label` VARCHAR(255) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`import_export_profile_id`, `language_id`),
                CONSTRAINT `fk.import_export_profile_translation.import_export_profile_id` FOREIGN KEY (`import_export_profile_id`)
                    REFERENCES `import_export_profile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.import_export_profile_translation.language_id` FOREIGN KEY (`language_id`)
                    REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $translationsPresent = $connection->fetchOne('SELECT 1 FROM `import_export_profile_translation` LIMIT 1;');

        if ($translationsPresent !== false) {
            return;
        }

        $defaultLanguageId = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        $englishLanguageId = $connection->fetchOne('
            SELECT lang.id
            FROM language lang
            INNER JOIN locale loc ON lang.translation_code_id = loc.id
            AND loc.code = \'en-GB\';
        ');
        $chineseLanguageId = $connection->fetchOne('
            SELECT lang.id
            FROM language lang
            INNER JOIN locale loc ON lang.translation_code_id = loc.id
            AND loc.code = \'zh-CN\';
        ');

        $insertNamesAsLabelsStatement = $connection->prepare('
            INSERT INTO `import_export_profile_translation` (`import_export_profile_id`, `language_id`, `label`, `created_at`)
            SELECT `id`, :languageId, `name`, NOW()
            FROM `import_export_profile`;
        ');

        $insertChineseLabelsStatement = $connection->prepare('
            CREATE TEMPORARY TABLE `temp_import_export_profile_translation` (id int(11) NOT NULL, PRIMARY KEY (id));
            SELECT `id`, `name` AS `label` FROM import_export_profile;
            UPDATE `temp_import_export_profile_translation` SET `label` = \'类目\' WHERE `label` = \'Default category\';
            UPDATE `temp_import_export_profile_translation` SET `label` = \'媒体\' WHERE `label` = \'Default media\';
            UPDATE `temp_import_export_profile_translation` SET `label` = \'商品变体配置\' WHERE `label` = \'Default variant configuration settings\';
            UPDATE `temp_import_export_profile_translation` SET `label` = \'邮件订阅\' WHERE `label` = \'Default newsletter recipient\';
            UPDATE `temp_import_export_profile_translation` SET `label` = \'属性\' WHERE `label` = \'Default properties\';
            UPDATE `temp_import_export_profile_translation` SET `label` = \'商品\' WHERE `label` = \'Default product\';

            INSERT INTO `import_export_profile_translation` (`import_export_profile_id`, `language_id`, `label`, `created_at`)
            SELECT `id`, :languageId, `label`, NOW()
            FROM `temp_import_export_profile_translation`;
        ');

        if (!\in_array($defaultLanguageId, [$englishLanguageId, $chineseLanguageId], true)) {
            $insertNamesAsLabelsStatement->executeStatement([
                'languageId' => $defaultLanguageId,
            ]);
        }

        if ($englishLanguageId) {
            $insertNamesAsLabelsStatement->executeStatement([
                'languageId' => $englishLanguageId,
            ]);
        }

        if ($chineseLanguageId) {
            $insertChineseLabelsStatement->executeStatement([
                'languageId' => $chineseLanguageId,
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

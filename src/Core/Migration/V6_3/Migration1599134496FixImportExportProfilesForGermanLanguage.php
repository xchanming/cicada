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
class Migration1599134496FixImportExportProfilesForGermanLanguage extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1599134496;
    }

    public function update(Connection $connection): void
    {
        $chineseLanguageId = $connection->fetchOne('
            SELECT lang.id
            FROM language lang
            INNER JOIN locale loc ON lang.locale_id = loc.id
            AND loc.code = \'zh-CN\';
        ');

        if (!$chineseLanguageId) {
            return;
        }

        $englishLanguageId = $connection->fetchOne('
            SELECT lang.id
            FROM language lang
            INNER JOIN locale loc ON lang.locale_id = loc.id
            AND loc.code = \'en-GB\';
        ');

        $sql = <<<'SQL'
            SELECT *
            FROM import_export_profile_translation AS `translation`
            INNER JOIN import_export_profile AS `profile` ON translation.import_export_profile_id = profile.id
            WHERE profile.system_default = 1
            AND language_id = :languageId
SQL;

        $englishData = $connection->fetchAllAssociative($sql, [
            'languageId' => $englishLanguageId,
        ]);
        $chineseData = $connection->fetchAllAssociative($sql, [
            'languageId' => $chineseLanguageId,
        ]);
        $germanTranslations = $this->getChineseTranslationData();

        $insertSql = <<<'SQL'
            INSERT INTO import_export_profile_translation (`import_export_profile_id`, `language_id`, `label`, `created_at`)
            VALUES (:import_export_profile_id, :language_id, :label, :created_at)
SQL;

        $stmt = $connection->prepare($insertSql);
        foreach ($englishData as $data) {
            if ($this->checkIfInGermanData($data, $chineseData)) {
                continue;
            }

            $stmt->executeStatement([
                'import_export_profile_id' => $data['import_export_profile_id'],
                'language_id' => $chineseLanguageId,
                'label' => $germanTranslations[$data['label']],
                'created_at' => $data['created_at'],
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    /**
     * @return array<string, string>
     */
    private function getChineseTranslationData(): array
    {
        return [
            'Default category' => '类目',
            'Default media' => '媒体',
            'Default variant configuration settings' => '产品变体配置',
            'Default newsletter recipient' => '邮件订阅',
            'Default properties' => '属性',
            'Default product' => '产品',
        ];
    }

    /**
     * @param array<string, mixed> $englishRow
     * @param array<array<string, mixed>> $germanData
     */
    private function checkIfInGermanData(array $englishRow, array $germanData): bool
    {
        $germanProfileIds = array_column($germanData, 'import_export_profile_id');

        return \in_array($englishRow['import_export_profile_id'], $germanProfileIds, true);
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Migration\Traits;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

#[Package('core')]
trait ImportTranslationsTrait
{
    protected function importTranslation(string $table, Translations $translations, Connection $connection): TranslationWriteResult
    {
        $englishIds = $this->getLanguageIds($connection, 'en-GB');
        $chineseIds = array_diff(
            array_merge($this->getLanguageIds($connection, 'zh-CN'), [Defaults::LANGUAGE_SYSTEM]),
            $englishIds
        );

        $columns = [];
        $values = [];

        $keys = array_merge($translations->getColumns(), ['created_at', 'language_id']);
        foreach ($keys as $column) {
            $columns[] = '`' . $column . '`';
            $values[] = ':' . $column;
        }

        $sql = str_replace(
            ['#columns#', '#values#', '#table#'],
            [
                implode(',', $columns),
                implode(',', $values),
                '`' . $table . '`',
            ],
            'REPLACE INTO #table# (#columns#) VALUES (#values#)'
        );

        foreach ($chineseIds as $id) {
            $data = array_merge($translations->getChinese(), [
                'language_id' => Uuid::fromHexToBytes($id),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);

            $connection->executeStatement($sql, $data);
        }

        foreach ($englishIds as $id) {
            $data = array_merge($translations->getEnglish(), [
                'language_id' => Uuid::fromHexToBytes($id),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);

            $connection->executeStatement($sql, $data);
        }

        return new TranslationWriteResult($chineseIds, $englishIds);
    }

    /**
     * @return array<string>
     */
    protected function getLanguageIds(Connection $connection, string $locale): array
    {
        $ids = $connection->fetchFirstColumn('
            SELECT LOWER(HEX(`language`.id)) as id
            FROM `language`
            INNER JOIN locale
                ON `language`.`locale_id` = `locale`.`id`
                AND locale.code = :locale
        ', ['locale' => $locale]);

        return array_unique(array_filter($ids));
    }
}

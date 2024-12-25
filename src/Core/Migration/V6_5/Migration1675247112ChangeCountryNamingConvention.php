<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('services-settings')]
class Migration1675247112ChangeCountryNamingConvention extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1675247112;
    }

    public function update(Connection $connection): void
    {
        $getCountrySql = <<<'SQL'
            SELECT `id`
            FROM country
            WHERE `iso` = :iso
            AND `iso3` = :iso3
        SQL;

        $countryId = $connection->fetchOne($getCountrySql, ['iso' => 'US', 'iso3' => 'USA']);

        if (!$countryId) {
            return;
        }

        // Update for EN
        $getEnLanguageSql = <<<'SQL'
            SELECT language.id
            FROM language
            JOIN locale ON locale.id = language.locale_id
            WHERE locale.code = 'en-GB'
        SQL;

        $enLanguageId = $connection->fetchOne($getEnLanguageSql);

        if ($enLanguageId) {
            $connection->update('country_translation', [
                'name' => 'United States of America',
                'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ], [
                'language_id' => $enLanguageId,
                'name' => 'USA',
                'country_id' => $countryId,
            ]);
        }

        // Update for DE
        $getDeLanguageSql = <<<'SQL'
            SELECT language.id
            FROM language
            JOIN locale ON locale.id = language.locale_id
            WHERE locale.code = 'zh-CN'
        SQL;

        $deLanguageId = $connection->fetchOne($getDeLanguageSql);

        if ($deLanguageId) {
            $connection->update('country_translation', [
                'name' => 'Vereinigte Staaten von Amerika',
                'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ], [
                'language_id' => $deLanguageId,
                'name' => 'USA',
                'country_id' => $countryId,
            ]);
        }
    }
}

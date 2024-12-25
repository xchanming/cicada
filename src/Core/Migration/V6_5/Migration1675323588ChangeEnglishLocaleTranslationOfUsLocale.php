<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('services-settings')]
class Migration1675323588ChangeEnglishLocaleTranslationOfUsLocale extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1675323588;
    }

    public function update(Connection $connection): void
    {
        $usLocaleId = $connection->fetchOne(
            '
            SELECT locale.id
            FROM `locale`
            WHERE LOWER(locale.code) = LOWER(:iso)',
            ['iso' => 'en-us']
        );

        if (!$usLocaleId) {
            return;
        }

        $enLangId = $this->fetchLanguageId('en-GB', $connection);
        if ($enLangId) {
            $connection->executeStatement(
                'UPDATE locale_translation
                SET name = :newName
                WHERE locale_id = :locale_id AND language_id = :language_id
                AND name = :oldName',
                [
                    'locale_id' => $usLocaleId,
                    'language_id' => $enLangId,
                    'oldName' => 'English',
                    'newName' => 'English (US)',
                ]
            );
        }

        $deLangId = $this->fetchLanguageId('zh-CN', $connection);
        if ($deLangId) {
            $connection->executeStatement(
                'UPDATE locale_translation
            SET name = :newName
            WHERE locale_id = :locale_id AND language_id = :language_id
            AND name = :oldName',
                [
                    'locale_id' => $usLocaleId,
                    'language_id' => $deLangId,
                    'oldName' => 'Englisch',
                    'newName' => 'Englisch (US)',
                ]
            );
        }
    }

    private function fetchLanguageId(string $code, Connection $connection): ?string
    {
        $langId = $connection->fetchOne(
            'SELECT `language`.`id` FROM `language` INNER JOIN `locale` ON `language`.`translation_code_id` = `locale`.`id` WHERE `code` = :code LIMIT 1',
            ['code' => $code]
        );
        if ($langId === false) {
            return null;
        }

        return (string) $langId;
    }
}

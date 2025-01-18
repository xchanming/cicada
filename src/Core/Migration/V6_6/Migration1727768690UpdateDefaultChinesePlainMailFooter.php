<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1727768690UpdateDefaultChinesePlainMailFooter extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1727768690;
    }

    public function update(Connection $connection): void
    {
        $defaultLanguageId = $this->fetchDefaultLanguageId($connection);

        $zhPlainFooterFilePath = __DIR__ . '/../Fixtures/mails/defaultMailFooter/zh-plain.twig';
        $zhPlainFooter = \file_get_contents($zhPlainFooterFilePath);
        \assert($zhPlainFooter !== false);

        $systemDefaultMailHeaderFooterId = $connection->fetchOne('SELECT `id` FROM `mail_header_footer` WHERE `system_default` = 1');

        $sqlString = 'UPDATE `mail_header_footer_translation` SET `footer_plain` = :footerPlain  WHERE `mail_header_footer_id`= :mailHeaderFooterId AND `language_id` = :enLangId AND `updated_at` IS NULL';
        $connection->executeStatement($sqlString, [
            'footerPlain' => $zhPlainFooter,
            'mailHeaderFooterId' => $systemDefaultMailHeaderFooterId,
            'enLangId' => $defaultLanguageId,
        ]);
    }

    private function fetchDefaultLanguageId(Connection $connection): string
    {
        $code = 'zh-CN';
        $langId = $connection->fetchOne('
        SELECT `language`.`id` FROM `language` INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id` WHERE `code` = :code LIMIT 1
        ', ['code' => $code]);

        if (!$langId) {
            return Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        }

        return $langId;
    }
}

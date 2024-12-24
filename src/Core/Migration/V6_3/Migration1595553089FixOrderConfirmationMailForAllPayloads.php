<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Content\MailTemplate\MailTemplateTypes;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1595553089FixOrderConfirmationMailForAllPayloads extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1595553089;
    }

    public function update(Connection $connection): void
    {
        $enLangId = $this->fetchLanguageId('en-GB', $connection);
        $deLangId = $this->fetchLanguageId('de-DE', $connection);

        $mailTemplateContent = require __DIR__ . '/../Fixtures/MailTemplateContent.php';

        // update order confirmation email templates
        $this->updateMailTemplate(
            MailTemplateTypes::MAILTYPE_ORDER_CONFIRM,
            $connection,
            $enLangId,
            $deLangId,
            $mailTemplateContent['OrderConfirmation']['en-GB']['html'],
            $mailTemplateContent['OrderConfirmation']['en-GB']['plain'],
            $mailTemplateContent['OrderConfirmation']['de-DE']['html'],
            $mailTemplateContent['OrderConfirmation']['de-DE']['plain']
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function fetchLanguageId(string $code, Connection $connection): ?string
    {
        /** @var string|null $langId */
        $langId = $connection->fetchOne('
        SELECT `language`.`id` FROM `language` INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id` WHERE `code` = :code LIMIT 1
        ', ['code' => $code]);

        if (!$langId) {
            return null;
        }

        return $langId;
    }

    private function updateMailTemplate(
        string $mailTemplateType,
        Connection $connection,
        ?string $enLangId,
        ?string $deLangId,
        string $getHtmlTemplateEn,
        string $getPlainTemplateEn,
        string $getHtmlTemplateDe,
        string $getPlainTemplateDe
    ): void {
        $templateId = $this->fetchSystemMailTemplateIdFromType($connection, $mailTemplateType);

        if ($templateId !== null) {
            // updating available entities of mail template
            $availableEntities = $this->fetchSystemMailTemplateAvailableEntitiesFromType($connection, $mailTemplateType);
            if (!isset($availableEntities['editOrderUrl'])) {
                $availableEntities['editOrderUrl'] = null;
                $sqlStatement = 'UPDATE `mail_template_type` SET `available_entities` = :availableEntities WHERE `technical_name` = :mailTemplateType AND `updated_at` IS NULL';
                $connection->executeStatement($sqlStatement, ['availableEntities' => json_encode($availableEntities, \JSON_THROW_ON_ERROR), 'mailTemplateType' => $mailTemplateType]);
            }

            $this->updateMailTemplateTranslation(
                $connection,
                $templateId,
                $enLangId,
                $getHtmlTemplateEn,
                $getPlainTemplateEn
            );

            $this->updateMailTemplateTranslation(
                $connection,
                $templateId,
                $deLangId,
                $getHtmlTemplateDe,
                $getPlainTemplateDe
            );
        }
    }

    private function fetchSystemMailTemplateIdFromType(Connection $connection, string $mailTemplateType): ?string
    {
        $templateTypeId = $connection->executeQuery('
        SELECT `id` from `mail_template_type` WHERE `technical_name` = :type
        ', ['type' => $mailTemplateType])->fetchOne();

        $templateId = $connection->executeQuery('
        SELECT `id` from `mail_template` WHERE `mail_template_type_id` = :typeId AND `system_default` = 1 AND `updated_at` IS NULL
        ', ['typeId' => $templateTypeId])->fetchOne();

        if ($templateId === false || !\is_string($templateId)) {
            return null;
        }

        return $templateId;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchSystemMailTemplateAvailableEntitiesFromType(Connection $connection, string $mailTemplateType): array
    {
        $availableEntities = $connection->executeQuery(
            'SELECT `available_entities` FROM `mail_template_type` WHERE `technical_name` = :mailTemplateType AND updated_at IS NULL;',
            ['mailTemplateType' => $mailTemplateType]
        )->fetchOne();

        if ($availableEntities === false || !\is_string($availableEntities) || json_decode($availableEntities, true, 512, \JSON_THROW_ON_ERROR) === null) {
            return [];
        }

        return json_decode($availableEntities, true, 512, \JSON_THROW_ON_ERROR);
    }

    private function updateMailTemplateTranslation(
        Connection $connection,
        string $mailTemplateId,
        ?string $langId,
        ?string $contentHtml,
        ?string $contentPlain,
        ?string $senderName = null
    ): void {
        if (!$langId) {
            return;
        }

        $sqlString = '';
        $sqlParams = [
            'templateId' => $mailTemplateId,
            'langId' => $langId,
        ];

        if ($contentHtml !== null) {
            $sqlString .= '`content_html` = :contentHtml ';
            $sqlParams['contentHtml'] = $contentHtml;
        }

        if ($contentPlain !== null) {
            $sqlString .= ($sqlString !== '' ? ', ' : '') . '`content_plain` = :contentPlain ';
            $sqlParams['contentPlain'] = $contentPlain;
        }

        if ($senderName !== null) {
            $sqlString .= ($sqlString !== '' ? ', ' : '') . '`sender_name` = :senderName ';
            $sqlParams['senderName'] = $senderName;
        }

        $sqlString = 'UPDATE `mail_template_translation` SET ' . $sqlString . 'WHERE `mail_template_id`= :templateId AND `language_id` = :langId AND `updated_at` IS NULL';

        $connection->executeStatement($sqlString, $sqlParams);
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Content\MailTemplate\MailTemplateTypes;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Migration\Traits\MailSubjectUpdate;
use Cicada\Core\Migration\Traits\MailUpdate;
use Cicada\Core\Migration\Traits\UpdateMailTrait;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1638514913RemovedUnusedVarsInMailTemplates extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1638514913;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            UPDATE `mail_template_translation`
            SET `description` = \'Anfrage zum Zurücksetzen des Passworts\'
            WHERE `description` = \'Passwort zurücksetzen Anfrage\'
            AND `updated_at` IS NULL;
        ');

        $connection->executeStatement(\sprintf('
            UPDATE `mail_template_type`
            SET `available_entities` = REPLACE(`available_entities`, \'urlResetPassword\', \'resetUrl\')
            WHERE `technical_name` = \'%s\'
        ', MailTemplateTypes::MAILTYPE_PASSWORD_CHANGE));

        $update = new MailUpdate(
            MailTemplateTypes::MAILTYPE_PASSWORD_CHANGE,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/password_change/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/password_change/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/password_change/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/password_change/de-html.html.twig'),
        );
        $this->updateMail($update, $connection);

        $update = new MailSubjectUpdate(
            MailTemplateTypes::MAILTYPE_USER_RECOVERY_REQUEST,
            null,
            '密码恢复'
        );
        $this->updateDeMailSubject($connection, $update);

        $update = new MailSubjectUpdate(
            MailTemplateTypes::MAILTYPE_CUSTOMER_RECOVERY_REQUEST,
            null,
            '密码恢复'
        );
        $this->updateDeMailSubject($connection, $update);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

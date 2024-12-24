<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Doctrine\DBAL\Connection;
use Cicada\Core\Content\MailTemplate\MailTemplateTypes;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Migration\Traits\MailUpdate;
use Cicada\Core\Migration\Traits\UpdateMailTrait;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1625554302UpdateMailTemplateForContactForm extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1625554302;
    }

    public function update(Connection $connection): void
    {
        $update = new MailUpdate(
            MailTemplateTypes::MAILTYPE_CONTACT_FORM,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/contact_form/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/contact_form/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/contact_form/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/contact_form/de-html.html.twig')
        );

        $this->updateMail($update, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Content\MailTemplate\MailTemplateTypes;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Migration\Traits\MailUpdate;
use Cicada\Core\Migration\Traits\UpdateMailTrait;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1701688920FixDownloadLinkMail extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1701688920;
    }

    public function update(Connection $connection): void
    {
        $update = new MailUpdate(
            MailTemplateTypes::MAILTYPE_DOWNLOADS_DELIVERY,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/downloads_delivery/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/downloads_delivery/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/downloads_delivery/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/downloads_delivery/de-html.html.twig')
        );

        $this->updateMail($update, $connection);
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Content\MailTemplate\MailTemplateTypes;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Migration\Traits\MailUpdate;
use Cicada\Core\Migration\Traits\UpdateMailTrait;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1669316067ChangeColumnTitleInDownloadsDeliveryMailTemplate extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1669316067;
    }

    public function update(Connection $connection): void
    {
        $updateDownloadsDeliveryMailTemplate = new MailUpdate(
            MailTemplateTypes::MAILTYPE_DOWNLOADS_DELIVERY,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/downloads_delivery/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/downloads_delivery/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/downloads_delivery/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/downloads_delivery/de-html.html.twig'),
        );
        $this->updateMail($updateDownloadsDeliveryMailTemplate, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

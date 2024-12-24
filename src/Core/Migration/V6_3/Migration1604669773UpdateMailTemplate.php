<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

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
class Migration1604669773UpdateMailTemplate extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1604669773;
    }

    public function update(Connection $connection): void
    {
        $update = new MailUpdate(
            'contact_form',
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/contact_form/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/contact_form/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/contact_form/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/contact_form/de-html.html.twig')
        );

        $this->updateMail($update, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

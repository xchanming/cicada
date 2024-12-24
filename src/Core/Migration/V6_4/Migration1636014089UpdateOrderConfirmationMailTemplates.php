<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

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
class Migration1636014089UpdateOrderConfirmationMailTemplates extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1636014089;
    }

    public function update(Connection $connection): void
    {
        $update = new MailUpdate(
            'order_confirmation_mail',
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_confirmation_mail/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_confirmation_mail/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_confirmation_mail/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_confirmation_mail/de-html.html.twig'),
        );

        $this->updateMail($update, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

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
class Migration1617864895UpdateMailTemplateForNestedLineItems extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1617864895;
    }

    public function update(Connection $connection): void
    {
        $update = new MailUpdate(
            MailTemplateTypes::MAILTYPE_ORDER_CONFIRM,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_confirmation_mail/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_confirmation_mail/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_confirmation_mail/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_confirmation_mail/de-html.html.twig')
        );

        $this->updateMail($update, $connection);

        $update = new MailUpdate(
            MailTemplateTypes::MAILTYPE_STATE_ENTER_ORDER_TRANSACTION_STATE_CANCELLED,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_transaction.state.cancelled/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_transaction.state.cancelled/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_transaction.state.cancelled/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_transaction.state.cancelled/de-html.html.twig')
        );

        $this->updateMail($update, $connection);

        $update = new MailUpdate(
            MailTemplateTypes::MAILTYPE_STATE_ENTER_ORDER_TRANSACTION_STATE_PAID,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_transaction.state.paid/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_transaction.state.paid/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_transaction.state.paid/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order_transaction.state.paid/de-html.html.twig')
        );

        $this->updateMail($update, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}

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
class Migration1660814397UpdateOrderCancelledMailTemplate extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1660814397;
    }

    public function update(Connection $connection): void
    {
        $update = new MailUpdate(
            MailTemplateTypes::MAILTYPE_STATE_ENTER_ORDER_STATE_CANCELLED,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order.state.cancelled/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order.state.cancelled/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order.state.cancelled/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/order.state.cancelled/de-html.html.twig')
        );

        $this->updateMail($update, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

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
class Migration1663238480FixMailTemplateFallbackChainUsage extends MigrationStep
{
    use UpdateMailTrait;

    public function getCreationTimestamp(): int
    {
        return 1663238480;
    }

    public function update(Connection $connection): void
    {
        $updateCustomerGroupRegistrationAcceptedMail = new MailUpdate(
            MailTemplateTypes::MAILTYPE_CUSTOMER_GROUP_REGISTRATION_ACCEPTED,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer.group.registration.accepted/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer.group.registration.accepted/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer.group.registration.accepted/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer.group.registration.accepted/de-html.html.twig'),
        );
        $this->updateMail($updateCustomerGroupRegistrationAcceptedMail, $connection);

        $updateCustomerGroupRegistrationDeclinedMail = new MailUpdate(
            MailTemplateTypes::MAILTYPE_CUSTOMER_GROUP_REGISTRATION_DECLINED,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer.group.registration.declined/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer.group.registration.declined/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer.group.registration.declined/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer.group.registration.declined/de-html.html.twig'),
        );
        $this->updateMail($updateCustomerGroupRegistrationDeclinedMail, $connection);

        $updateCustomerGroupChangeAcceptMail = new MailUpdate(
            MailTemplateTypes::MAILTYPE_CUSTOMER_GROUP_CHANGE_ACCEPT,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer_group_change_accept/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer_group_change_accept/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer_group_change_accept/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer_group_change_accept/de-html.html.twig'),
        );
        $this->updateMail($updateCustomerGroupChangeAcceptMail, $connection);

        $updateCustomerGroupChangeRejectMail = new MailUpdate(
            MailTemplateTypes::MAILTYPE_CUSTOMER_GROUP_CHANGE_REJECT,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer_group_change_reject/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer_group_change_reject/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer_group_change_reject/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/customer_group_change_reject/de-html.html.twig'),
        );
        $this->updateMail($updateCustomerGroupChangeRejectMail, $connection);

        $updateGuestOrderDoubleOptInMail = new MailUpdate(
            MailTemplateTypes::MAILTYPE_GUEST_ORDER_DOUBLE_OPT_IN,
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/guest_order.double_opt_in/en-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/guest_order.double_opt_in/en-html.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/guest_order.double_opt_in/de-plain.html.twig'),
            (string) file_get_contents(__DIR__ . '/../Fixtures/mails/guest_order.double_opt_in/de-html.html.twig'),
        );
        $this->updateMail($updateGuestOrderDoubleOptInMail, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}

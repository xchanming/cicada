<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate;

use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class MailTemplateTypes
{
    final public const MAILTYPE_PASSWORD_CHANGE = 'password_change';

    final public const MAILTYPE_USER_RECOVERY_REQUEST = 'user.recovery.request';

    final public const MAILTYPE_CUSTOMER_RECOVERY_REQUEST = 'customer.recovery.request';

    final public const MAILTYPE_CUSTOMER_REGISTER = 'customer_register';

    final public const MAILTYPE_DOWNLOADS_DELIVERY = 'downloads_delivery';
}

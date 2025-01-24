<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
#[IsFlowEventAware]
interface MailAware
{
    public const MAIL_STRUCT = 'mailStruct';

    public const SALES_CHANNEL_ID = 'salesChannelId';

    public function getMailStruct(): MailRecipientStruct;

    public function getSalesChannelId(): ?string;
}

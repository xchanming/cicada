<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('after-sales')]
class NewsletterRecipientTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'delete_newsletter_recipient_task';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}

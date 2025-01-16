<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Aware;

use Cicada\Core\Framework\Event\IsFlowEventAware;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
#[IsFlowEventAware]
interface NewsletterRecipientAware
{
    public const NEWSLETTER_RECIPIENT_ID = 'newsletterRecipientId';

    public const NEWSLETTER_RECIPIENT = 'newsletterRecipient';

    public function getNewsletterRecipientId(): string;
}

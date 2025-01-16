<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class NewsletterRecipientIndexingMessage extends EntityIndexingMessage
{
    private bool $deletedNewsletterRecipients = false;

    public function isDeletedNewsletterRecipients(): bool
    {
        return $this->deletedNewsletterRecipients;
    }

    public function setDeletedNewsletterRecipients(bool $deletedNewsletterRecipients): void
    {
        $this->deletedNewsletterRecipients = $deletedNewsletterRecipients;
    }
}

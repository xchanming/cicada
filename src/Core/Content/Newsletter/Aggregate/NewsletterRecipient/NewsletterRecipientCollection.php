<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<NewsletterRecipientEntity>
 */
#[Package('after-sales')]
class NewsletterRecipientCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'newsletter_recipient_collection';
    }

    protected function getExpectedClass(): string
    {
        return NewsletterRecipientEntity::class;
    }
}

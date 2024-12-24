<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class AccountNewsletterRecipientRouteResponse extends StoreApiResponse
{
    /**
     * @var AccountNewsletterRecipientResult
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<NewsletterRecipientCollection> $newsletterRecipients
     */
    public function __construct(EntitySearchResult $newsletterRecipients)
    {
        $firstNewsletterRecipient = $newsletterRecipients->getEntities()->first();
        if ($firstNewsletterRecipient) {
            $accNlRecipientResult = new AccountNewsletterRecipientResult($firstNewsletterRecipient->getStatus());
            parent::__construct($accNlRecipientResult);

            return;
        }
        $accNlRecipientResult = new AccountNewsletterRecipientResult();
        parent::__construct($accNlRecipientResult);
    }

    public function getAccountNewsletterRecipient(): AccountNewsletterRecipientResult
    {
        return $this->object;
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\SalesChannel;

use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('buyers-experience')]
class SalesChannelNewsletterRecipientDefinition extends NewsletterRecipientDefinition implements SalesChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
        $criteria->addFilter(new EqualsFilter('salesChannel.id', $context->getSalesChannelId()));
    }
}

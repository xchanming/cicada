<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\SeoUrl\SalesChannel;

use Cicada\Core\Content\Seo\SeoUrl\SeoUrlDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('buyers-experience')]
class SalesChannelSeoUrlDefinition extends SeoUrlDefinition implements SalesChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
        $criteria->addFilter(new EqualsFilter('languageId', $context->getLanguageId()));
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('salesChannelId', $context->getSalesChannelId()),
            new EqualsFilter('salesChannelId', null),
        ]));
        $criteria->addFilter(new EqualsFilter('isCanonical', true));
        $criteria->addFilter(new EqualsFilter('isDeleted', false));
    }
}

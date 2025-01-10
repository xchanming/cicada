<?php declare(strict_types=1);

namespace Cicada\Core\System\Language\SalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageDefinition;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('fundamentals@discovery')]
class SalesChannelLanguageDefinition extends LanguageDefinition implements SalesChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
        $criteria->addFilter(new EqualsFilter('language.salesChannels.id', $context->getSalesChannelId()));
    }
}

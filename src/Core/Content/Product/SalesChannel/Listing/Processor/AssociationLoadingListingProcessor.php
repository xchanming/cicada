<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Listing\Processor;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('inventory')]
class AssociationLoadingListingProcessor extends AbstractListingProcessor
{
    public function getDecorated(): AbstractListingProcessor
    {
        throw new DecorationPatternException(self::class);
    }

    public function prepare(Request $request, Criteria $criteria, SalesChannelContext $context): void
    {
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('options');
    }
}

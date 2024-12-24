<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SearchKeyword;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('services-settings')]
interface ProductSearchBuilderInterface
{
    public function build(Request $request, Criteria $criteria, SalesChannelContext $context): void;
}

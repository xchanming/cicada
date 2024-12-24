<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
abstract class AbstractProductCloseoutFilterFactory
{
    abstract public function getDecorated(): AbstractProductCloseoutFilterFactory;

    abstract public function create(SalesChannelContext $context): MultiFilter;
}

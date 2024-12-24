<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
interface EntitySearcherInterface
{
    public function search(EntityDefinition $definition, Criteria $criteria, Context $context): IdSearchResult;
}

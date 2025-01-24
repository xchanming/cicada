<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
interface CriteriaPartInterface
{
    /**
     * @return list<string>
     */
    public function getFields(): array;
}

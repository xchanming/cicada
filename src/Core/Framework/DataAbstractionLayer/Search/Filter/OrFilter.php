<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\Filter;

use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('framework')]
class OrFilter extends MultiFilter
{
    /**
     * @param Filter[] $queries
     */
    public function __construct(array $queries = [])
    {
        parent::__construct(self::CONNECTION_OR, $queries);
    }
}

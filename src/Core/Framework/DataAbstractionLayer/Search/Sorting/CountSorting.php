<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting;

use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('framework')]
class CountSorting extends FieldSorting
{
    protected string $type = 'count';
}

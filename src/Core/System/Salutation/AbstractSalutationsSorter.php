<?php declare(strict_types=1);

namespace Cicada\Core\System\Salutation;

use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
abstract class AbstractSalutationsSorter
{
    abstract public function getDecorated(): AbstractSalutationsSorter;

    abstract public function sort(SalutationCollection $salutations): SalutationCollection;
}

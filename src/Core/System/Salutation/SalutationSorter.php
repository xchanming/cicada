<?php declare(strict_types=1);

namespace Cicada\Core\System\Salutation;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

#[Package('buyers-experience')]
class SalutationSorter extends AbstractSalutationsSorter
{
    public function getDecorated(): AbstractSalutationsSorter
    {
        throw new DecorationPatternException(self::class);
    }

    public function sort(SalutationCollection $salutations): SalutationCollection
    {
        $salutations->sort(function (SalutationEntity $a, SalutationEntity $b) {
            if ($a->getSalutationKey() === SalutationDefinition::NOT_SPECIFIED) {
                return -1;
            }

            if ($b->getSalutationKey() === SalutationDefinition::NOT_SPECIFIED) {
                return 1;
            }

            return $b->getSalutationKey() <=> $a->getSalutationKey();
        });

        return $salutations;
    }
}

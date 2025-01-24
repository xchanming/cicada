<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\Term;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
interface TokenizerInterface
{
    /**
     * @return list<string>
     */
    public function tokenize(string $string): array;
}

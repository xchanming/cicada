<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Search\Term\Filter;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\Service\ResetInterface;

#[Package('framework')]
abstract class AbstractTokenFilter implements ResetInterface
{
    final public const DEFAULT_MIN_SEARCH_TERM_LENGTH = 2;

    public function reset(): void
    {
        $this->getDecorated()->reset();
    }

    abstract public function getDecorated(): AbstractTokenFilter;

    /**
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    abstract public function filter(array $tokens, Context $context): array;
}

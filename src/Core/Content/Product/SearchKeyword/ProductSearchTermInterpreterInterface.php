<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SearchKeyword;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\SearchPattern;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
interface ProductSearchTermInterpreterInterface
{
    public function interpret(string $word, Context $context): SearchPattern;
}

<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SearchKeyword;

use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
interface ProductSearchKeywordAnalyzerInterface
{
    /**
     * @param array<int, array{field: string, tokenize: '1'|'0'|bool, ranking: numeric-string|int|float}> $configFields
     */
    public function analyze(ProductEntity $product, Context $context, array $configFields): AnalyzedKeywordCollection;
}

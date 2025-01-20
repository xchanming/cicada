<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SearchKeyword;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Query\ScoreQuery;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Term\SearchPattern;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Package('services-settings')]
class ProductSearchBuilder implements ProductSearchBuilderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ProductSearchTermInterpreterInterface $interpreter,
        private readonly LoggerInterface $logger,
        private readonly int $searchTermMaxLength
    ) {
    }

    public function build(Request $request, Criteria $criteria, SalesChannelContext $context): void
    {
        $search = $request->get('search');

        if (\is_array($search)) {
            $term = implode(' ', $search);
        } else {
            $term = (string) $search;
        }

        $term = trim($term);
        if (mb_strlen($term) > $this->searchTermMaxLength) {
            $this->logger->notice(
                'The search term "{term}" was trimmed because it exceeded the maximum length of {maxLength} characters.',
                ['term' => $term, 'maxLength' => $this->searchTermMaxLength]
            );

            $term = mb_substr($term, 0, $this->searchTermMaxLength);
        }

        if (empty($term)) {
            throw RoutingException::missingRequestParameter('search');
        }

        $pattern = $this->interpreter->interpret($term, $context->getContext());

        foreach ($pattern->getTerms() as $searchTerm) {
            $criteria->addQuery(
                new ScoreQuery(
                    new EqualsFilter('product.searchKeywords.keyword', $searchTerm->getTerm()),
                    $searchTerm->getScore(),
                    'product.searchKeywords.ranking'
                )
            );
        }
        $criteria->addQuery(
            new ScoreQuery(
                new ContainsFilter('product.searchKeywords.keyword', $pattern->getOriginal()->getTerm()),
                $pattern->getOriginal()->getScore(),
                'product.searchKeywords.ranking'
            )
        );

        if ($pattern->getBooleanClause() !== SearchPattern::BOOLEAN_CLAUSE_AND) {
            $criteria->addFilter(new AndFilter([
                new EqualsAnyFilter('product.searchKeywords.keyword', array_values($pattern->getAllTerms())),
                new EqualsFilter('product.searchKeywords.languageId', $context->getLanguageId()),
            ]));

            return;
        }

        foreach ($pattern->getTokenTerms() as $terms) {
            $criteria->addFilter(new AndFilter([
                new EqualsFilter('product.searchKeywords.languageId', $context->getLanguageId()),
                new EqualsAnyFilter('product.searchKeywords.keyword', $terms),
            ]));
        }
    }
}

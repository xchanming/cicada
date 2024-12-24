<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

/**
 * @final Depend on the AbstractRuleLoader which is the definition of public API for this scope
 */
#[Package('checkout')]
class RuleLoader extends AbstractRuleLoader
{
    /**
     * @internal
     *
     * @param EntityRepository<RuleCollection> $repository
     */
    public function __construct(private readonly EntityRepository $repository)
    {
    }

    public function getDecorated(): AbstractRuleLoader
    {
        throw new DecorationPatternException(self::class);
    }

    public function load(Context $context): RuleCollection
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('priority', FieldSorting::DESCENDING));
        $criteria->addSorting(new FieldSorting('id'));
        $criteria->addFilter(new EqualsFilter('invalid', false));
        $criteria->setLimit(500);
        $criteria->setTitle('cart-rule-loader::load-rules');

        $repositoryIterator = new RepositoryIterator($this->repository, $context, $criteria);
        $rules = new RuleCollection();

        while (($result = $repositoryIterator->fetch()) !== null) {
            foreach ($result->getEntities() as $rule) {
                if ($rule->getPayload()) {
                    $rules->add($rule);
                }
            }
            if ($result->count() < 500) {
                break;
            }
        }

        return $rules;
    }
}

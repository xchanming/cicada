<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\LineItem\Group\Exception\LineItemGroupPackagerNotFoundException;
use Cicada\Core\Checkout\Cart\LineItem\Group\Exception\LineItemGroupSorterNotFoundException;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupBuilder;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupDefinition;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\FilterRule;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

#[Package('services-settings')]
class LineItemGroupRule extends FilterRule
{
    final public const RULE_NAME = 'cartLineItemInGroup';

    protected string $groupId;

    protected string $packagerKey;

    protected float $value;

    protected string $sorterKey;

    protected ?RuleCollection $rules = null;

    /**
     * @throws CartException
     * @throws LineItemGroupPackagerNotFoundException
     * @throws LineItemGroupSorterNotFoundException
     */
    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        $groupDefinition = new LineItemGroupDefinition(
            $this->groupId,
            $this->packagerKey,
            $this->value,
            $this->sorterKey,
            $this->rules ?? new RuleCollection()
        );

        $builder = $scope->getCart()->getData()->get(LineItemGroupBuilder::class);
        if (!$builder instanceof LineItemGroupBuilder) {
            return false;
        }

        $results = $builder->findGroupPackages(
            [$groupDefinition],
            $scope->getCart(),
            $scope->getSalesChannelContext()
        );

        return $results->hasFoundItems();
    }

    public function getConstraints(): array
    {
        return [
            'groupId' => RuleConstraints::string(),
            'packagerKey' => RuleConstraints::string(),
            'value' => RuleConstraints::float(),
            'sorterKey' => RuleConstraints::string(),
            'rules' => [new NotBlank(), new Type('container')],
        ];
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Rule;

use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\FlowRule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\Tag\TagDefinition;

#[Package('after-sales')]
class OrderTagRule extends FlowRule
{
    final public const RULE_NAME = 'orderTag';

    /**
     * @internal
     *
     * @param list<string>|null $identifiers
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?array $identifiers = null
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof FlowRuleScope) {
            return false;
        }

        return RuleComparison::uuids($this->extractTagIds($scope->getOrder()), $this->identifiers, $this->operator);
    }

    public function getConstraints(): array
    {
        $constraints = [
            'operator' => RuleConstraints::uuidOperators(),
        ];

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $constraints;
        }

        $constraints['identifiers'] = RuleConstraints::uuids();

        return $constraints;
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, true, true)
            ->entitySelectField('identifiers', TagDefinition::ENTITY_NAME, true);
    }

    /**
     * @return array<string>
     */
    private function extractTagIds(OrderEntity $order): array
    {
        $tags = $order->getTags();

        if (!$tags) {
            return [];
        }

        return $tags->getIds();
    }
}

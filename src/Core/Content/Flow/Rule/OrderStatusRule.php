<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\FlowRule;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;

#[Package('services-settings')]
class OrderStatusRule extends FlowRule
{
    public const RULE_NAME = 'orderStatus';

    /**
     * @var array<string>
     */
    protected array $salutationIds = [];

    /**
     * @internal
     *
     * @param list<string> $stateIds
     */
    public function __construct(
        public string $operator = Rule::OPERATOR_EQ,
        public ?array $stateIds = null
    ) {
        parent::__construct();
    }

    public function getConstraints(): array
    {
        return [
            'operator' => RuleConstraints::uuidOperators(false),
            'stateIds' => RuleConstraints::uuids(),
        ];
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof FlowRuleScope || $this->stateIds === null) {
            return false;
        }

        return RuleComparison::stringArray($scope->getOrder()->getStateId(), $this->stateIds, $this->operator);
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField(
                'stateIds',
                StateMachineStateDefinition::ENTITY_NAME,
                true,
                [
                    'criteria' => [
                        'associations' => [
                            'stateMachine',
                        ],
                        'filters' => [
                            [
                                'type' => 'equals',
                                'field' => 'state_machine_state.stateMachine.technicalName',
                                'value' => 'order.state',
                            ],
                        ],
                    ],
                ]
            );
    }
}

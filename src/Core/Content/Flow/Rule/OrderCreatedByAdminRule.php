<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\FlowRule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class OrderCreatedByAdminRule extends FlowRule
{
    final public const RULE_NAME = 'orderCreatedByAdmin';

    /**
     * @internal
     */
    public function __construct(protected bool $shouldOrderBeCreatedByAdmin = true)
    {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof FlowRuleScope) {
            return false;
        }

        return $this->shouldOrderBeCreatedByAdmin === (bool) $scope->getOrder()->getCreatedById();
    }

    public function getConstraints(): array
    {
        return [
            'shouldOrderBeCreatedByAdmin' => RuleConstraints::bool(true),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())->booleanField('shouldOrderBeCreatedByAdmin');
    }
}

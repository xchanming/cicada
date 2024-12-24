<?php declare(strict_types=1);

namespace Cicada\Core\System\Currency\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\Currency\CurrencyDefinition;

#[Package('services-settings')]
class CurrencyRule extends Rule
{
    final public const RULE_NAME = 'currency';

    /**
     * @internal
     *
     * @param list<string>|null $currencyIds
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?array $currencyIds = null
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        return RuleComparison::uuids([$scope->getContext()->getCurrencyId()], $this->currencyIds, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'currencyIds' => RuleConstraints::uuids(),
            'operator' => RuleConstraints::uuidOperators(false),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField('currencyIds', CurrencyDefinition::ENTITY_NAME, true);
    }
}

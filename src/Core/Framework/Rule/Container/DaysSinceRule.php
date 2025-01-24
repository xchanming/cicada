<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Rule\Container;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedValueException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('fundamentals@after-sales')]
abstract class DaysSinceRule extends Rule
{
    protected string $operator = Rule::OPERATOR_EQ;

    protected ?float $daysPassed = null;

    public function match(RuleScope $scope): bool
    {
        if (!$this->supportsScope($scope)) {
            return false;
        }

        $currentDate = $scope->getCurrentTime()->setTime(0, 0, 0, 0);

        if ($this->daysPassed === null && $this->operator !== self::OPERATOR_EMPTY) {
            throw new UnsupportedValueException(\gettype($this->daysPassed), self::class);
        }

        if (!$date = $this->getDate($scope)) {
            return RuleComparison::isNegativeOperator($this->operator);
        }

        if ($this->daysPassed === null) {
            return false;
        }

        $dateTime = (new \DateTime())
            ->setTimestamp($date->getTimestamp())
            ->setTime(0, 0);

        /** @var \DateInterval $interval */
        $interval = $dateTime->diff($currentDate);

        if ($this->operator === self::OPERATOR_EMPTY) {
            return false;
        }

        return RuleComparison::numeric((int) $interval->days, $this->daysPassed, $this->operator);
    }

    public function getConstraints(): array
    {
        $constraints = [
            'operator' => RuleConstraints::numericOperators(),
        ];

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $constraints;
        }

        $constraints['daysPassed'] = RuleConstraints::float();

        return $constraints;
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_NUMBER, true)
            ->numberField('daysPassed', ['unit' => RuleConfig::UNIT_TIME]);
    }

    abstract protected function getDate(RuleScope $scope): ?\DateTimeInterface;

    abstract protected function supportsScope(RuleScope $scope): bool;
}

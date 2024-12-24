<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedValueException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class CartVolumeRule extends Rule
{
    final public const RULE_NAME = 'cartVolume';

    /**
     * @internal
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?float $volume = null
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        if ($this->volume === null) {
            throw new UnsupportedValueException(\gettype($this->volume), self::class);
        }

        return RuleComparison::numeric($this->calculateCartVolume($scope->getCart()), $this->volume * self::VOLUME_FACTOR, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'volume' => RuleConstraints::float(),
            'operator' => RuleConstraints::numericOperators(false),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_NUMBER)
            ->numberField('volume', ['unit' => RuleConfig::UNIT_VOLUME]);
    }

    private function calculateCartVolume(Cart $cart): float
    {
        $volume = 0.0;

        foreach ($cart->getDeliveries() as $delivery) {
            $volume += $delivery->getPositions()->getVolume();
        }

        return $volume;
    }
}

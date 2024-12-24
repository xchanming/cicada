<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\Checkout\Cart\Rule\AlwaysValidRule;
use Cicada\Core\Checkout\Cart\Rule\GoodsCountRule;
use Cicada\Core\Checkout\Cart\Rule\GoodsPriceRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemCustomFieldRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemGoodsTotalRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemGroupRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemInCategoryRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemPropertyRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemPurchasePriceRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemWithQuantityRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemWrapperRule;
use Cicada\Core\Checkout\Customer\Rule\BillingZipCodeRule;
use Cicada\Core\Checkout\Customer\Rule\CustomerCustomFieldRule;
use Cicada\Core\Checkout\Customer\Rule\ShippingZipCodeRule;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Rule\Container\Container;
use Cicada\Core\Framework\Rule\Container\FilterRule;
use Cicada\Core\Framework\Rule\Container\MatchAllLineItemsRule;
use Cicada\Core\Framework\Rule\Container\NotRule;
use Cicada\Core\Framework\Rule\Container\OrRule;
use Cicada\Core\Framework\Rule\Container\XorRule;
use Cicada\Core\Framework\Rule\Container\ZipCodeRule;
use Cicada\Core\Framework\Rule\DateRangeRule;
use Cicada\Core\Framework\Rule\Rule as CicadaRule;
use Cicada\Core\Framework\Rule\ScriptRule;
use Cicada\Core\Framework\Rule\SimpleRule;
use Cicada\Core\Framework\Rule\TimeRangeRule;
use Cicada\Core\Test\Stub\Rule\FalseRule;
use Cicada\Core\Test\Stub\Rule\TrueRule;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 *
 * @internal
 */
#[Package('core')]
class RuleConditionHasRuleConfigRule implements Rule
{
    /**
     * @var list<string>
     */
    private array $rulesAllowedToBeWithoutConfig = [
        ZipCodeRule::class,
        FilterRule::class,
        Container::class,
        AndRule::class,
        NotRule::class,
        OrRule::class,
        XorRule::class,
        MatchAllLineItemsRule::class,
        ScriptRule::class,
        DateRangeRule::class,
        SimpleRule::class,
        TimeRangeRule::class,
        GoodsCountRule::class,
        GoodsPriceRule::class,
        LineItemRule::class,
        LineItemWithQuantityRule::class,
        LineItemWrapperRule::class,
        BillingZipCodeRule::class,
        ShippingZipCodeRule::class,
        AlwaysValidRule::class,
        LineItemPropertyRule::class,
        LineItemPurchasePriceRule::class,
        LineItemInCategoryRule::class,
        LineItemCustomFieldRule::class,
        LineItemGoodsTotalRule::class,
        CustomerCustomFieldRule::class,
        LineItemGroupRule::class,
        FalseRule::class,
        TrueRule::class,
    ];

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return array<array-key, RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isRuleClass($scope) || $this->isAllowed($scope) || $this->isValid($scope)) {
            if ($this->isAllowed($scope) && $this->isValid($scope)) {
                return [
                    RuleErrorBuilder::message('This class is implementing the getConfig function and has a own admin component. Remove getConfig or the component.')
                        ->identifier('cicada.ruleConfig')
                        ->build(),
                ];
            }

            return [];
        }

        return [
            RuleErrorBuilder::message('This class has to implement getConfig or implement a new admin component.')
                ->identifier('cicada.ruleConfig')
                ->build(),
        ];
    }

    private function isValid(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        if ($class === null || !$class->hasMethod('getConfig')) {
            return false;
        }

        $declaringClass = $class->getMethod('getConfig', $scope)->getDeclaringClass();

        return $declaringClass->getName() !== CicadaRule::class;
    }

    private function isAllowed(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        if ($class === null) {
            return false;
        }

        return \in_array($class->getName(), $this->rulesAllowedToBeWithoutConfig, true);
    }

    private function isRuleClass(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        if ($class === null) {
            return false;
        }

        $namespace = $class->getName();
        if (!\str_contains($namespace, 'Cicada\\Tests\\Unit\\') && !\str_contains($namespace, 'Cicada\\Tests\\Migration\\')) {
            return false;
        }

        return $class->isSubclassOf(CicadaRule::class);
    }
}

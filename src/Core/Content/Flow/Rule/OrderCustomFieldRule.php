<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\CustomFieldRule;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\FlowRule;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('after-sales')]
class OrderCustomFieldRule extends FlowRule
{
    final public const RULE_NAME = 'orderCustomField';

    /**
     * @var array<string|int|bool|float>|string|int|bool|float|null
     */
    protected array|string|int|bool|float|null $renderedFieldValue = null;

    protected ?string $selectedField = null;

    protected ?string $selectedFieldSet = null;

    /**
     * @param array<string, string|array<string, string>> $renderedField
     *
     * @internal
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected array $renderedField = []
    ) {
        parent::__construct();
    }

    /**
     * @throws UnsupportedOperatorException
     */
    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof FlowRuleScope) {
            return false;
        }

        $orderCustomFields = $scope->getOrder()->getCustomFields() ?? [];

        return CustomFieldRule::match($this->renderedField, $this->renderedFieldValue, $this->operator, $orderCustomFields);
    }

    public function getConstraints(): array
    {
        return CustomFieldRule::getConstraints($this->renderedField);
    }
}

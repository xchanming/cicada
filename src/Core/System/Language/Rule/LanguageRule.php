<?php declare(strict_types=1);

namespace Cicada\Core\System\Language\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\Exception\UnsupportedValueException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\Language\LanguageDefinition;

#[Package('fundamentals@discovery')]
class LanguageRule extends Rule
{
    final public const RULE_NAME = 'language';

    /**
     * @internal
     *
     * @param list<string>|null $languageIds
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?array $languageIds = null
    ) {
        parent::__construct();
    }

    /**
     * @throws UnsupportedOperatorException|UnsupportedValueException
     */
    public function match(RuleScope $scope): bool
    {
        if ($this->languageIds === null) {
            throw new UnsupportedValueException(\gettype($this->languageIds), self::class);
        }

        return RuleComparison::uuids([$scope->getContext()->getLanguageId()], $this->languageIds, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'operator' => RuleConstraints::uuidOperators(false),
            'languageIds' => RuleConstraints::uuids(),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField('languageIds', LanguageDefinition::ENTITY_NAME, true);
    }
}

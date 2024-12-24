<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Rule;

use Cicada\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\FlowRule;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class OrderDocumentTypeRule extends FlowRule
{
    public const RULE_NAME = 'orderDocumentType';

    /**
     * @internal
     *
     * @param list<string> $documentIds
     */
    public function __construct(
        public string $operator = Rule::OPERATOR_EQ,
        public ?array $documentIds = null
    ) {
        parent::__construct();
    }

    public function getConstraints(): array
    {
        $constraints = [
            'operator' => RuleConstraints::uuidOperators(),
        ];

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $constraints;
        }

        $constraints['documentIds'] = RuleConstraints::uuids();

        return $constraints;
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof FlowRuleScope) {
            return false;
        }

        if (!$documents = $scope->getOrder()->getDocuments()) {
            return false;
        }

        $typeIds = [];
        foreach ($documents->getElements() as $document) {
            $typeIds[] = $document->getDocumentTypeId();
        }

        return RuleComparison::uuids(array_values(array_unique($typeIds)), $this->documentIds, $this->operator);
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, true, true)
            ->entitySelectField('documentIds', DocumentTypeDefinition::ENTITY_NAME, true);
    }
}

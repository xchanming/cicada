<?php

declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;
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
class NoFlowEventAwareExtendsRule implements Rule
{
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
        $reflection = $node->getClassReflection();

        if (!$reflection->isInterface()) {
            return [];
        }

        if (!$reflection->isSubclassOf(FlowEventAware::class)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf(
                'Class %s should not extend FlowEventAware. Flow events should not be derived from each other to make them easier to test',
                $reflection->getName()
            ))
                ->identifier('cicada.flowEventAwareExtend')
                ->build(),
        ];
    }
}

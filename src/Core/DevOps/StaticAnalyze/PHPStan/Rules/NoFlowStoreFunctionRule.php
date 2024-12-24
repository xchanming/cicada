<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\FlowStorer;
use Cicada\Core\Framework\Log\Package;

/**
 * @implements Rule<MethodCall>
 *
 * @internal
 */
#[Package('core')]
class NoFlowStoreFunctionRule implements Rule
{
    use InTestClassTrait;

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isInTestClass($scope)) {
            return [];
        }

        if (!$node instanceof MethodCall) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if ($node->name->name !== 'getStore') {
            return [];
        }

        if (!$node->var instanceof Variable) {
            return [];
        }

        if (!\is_string($node->var->name)) {
            return [];
        }

        $classNames = $scope->getVariableType($node->var->name)->getObjectClassNames();
        if (!\in_array(StorableFlow::class, $classNames, true)) {
            return [];
        }

        $class = $scope->getClassReflection();
        if ($class === null || $class->isSubclassOf(FlowStorer::class)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Using Cicada::getStore, outside storer classes, is not allowed. Use getData instead')
                ->identifier('cicada.noFlowStoreFunction')
                ->build(),
        ];
    }
}

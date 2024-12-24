<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Console\Command\Command;

/**
 * @implements Rule<StaticCall>
 *
 * @internal
 */
#[Package('core')]
class UseCLIContextRule implements Rule
{
    /**
     * @var list<class-string>
     */
    private array $baseClasses = [
        Command::class,
        ScheduledTaskHandler::class,
    ];

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || $node->name->name !== 'createDefaultContext') {
            return [];
        }

        if (!$node->class instanceof Name || $node->class->toString() !== 'Cicada\Core\Framework\Context') {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        foreach ($this->baseClasses as $baseClass) {
            if ($classReflection->isSubclassOf($baseClass)) {
                return [
                    RuleErrorBuilder::message('Method Context::createDefaultContext() should not be used in CLI context. Use Context::createCLIContext() instead.')
                        ->line($node->getLine())
                        ->identifier('cicada.cliContext')
                        ->build(),
                ];
            }
        }

        return [];
    }
}

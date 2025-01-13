<?php

declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGeneratorTest;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * This PHPStan rule prevents the manual creation of a `SalesChannelContext`.
 * It checks if the `SalesChannelContext` or any of its children are created manually.
 * Usually it should be sufficient to use the `SalesChannelContextFactory` or the `Generator::generateSalesChannelContext` method.
 *
 * @internal
 *
 * @implements Rule<New_>
 */
#[Package('core')]
class NoManualSalesChannelContextCreationRule implements Rule
{
    /**
     * @var list<class-string>
     */
    private static array $allowedClassesWhichCanCreateSalesChannelContext = [
        SalesChannelContextFactory::class,
        Generator::class,
        EntityCacheKeyGeneratorTest::class, // A bit complicated to refactor this test
    ];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof New_) {
            return [];
        }

        $class = $node->class;
        if (!$class instanceof Name) {
            return [];
        }

        $className = $class->toString();
        if (!$this->isSalesChannelContextOrChild($className)) {
            return [];
        }

        $currentClass = $scope->getClassReflection();
        if ($currentClass && \in_array($currentClass->getName(), self::$allowedClassesWhichCanCreateSalesChannelContext, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Manual creation of `Cicada\Core\System\SalesChannel\SalesChannelContext` is not allowed.')
                ->identifier('cicada.noManualSalesChannelContextCreation')
                ->addTip('Use `Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory` or `Cicada\Core\Test\Generator::createSalesChannelContext` instead.')
                ->build(),
        ];
    }

    private function isSalesChannelContextOrChild(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $class = $this->reflectionProvider->getClass($className);
        if ($class->getName() === SalesChannelContext::class) {
            return true;
        }

        return $class->isSubclassOf(SalesChannelContext::class);
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\Framework\Extensions\Extension;
use Cicada\Core\Framework\Log\Package;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\MissingConstantFromReflectionException;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassNode>
 *
 * @internal
 */
#[Package('core')]
class ExtensionRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return array<RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $example = $this->isExample($node);

        $extension = $this->isExtension($node);

        $internal = $this->isInternal($node->getDocComment()?->getText() ?? '');

        if (!$extension && !$example) {
            return [];
        }

        $errors = [];
        if ($internal) {
            $errors[] = RuleErrorBuilder::message('Extension / Example classes should not be marked as internal')
                ->identifier('cicada.extensionNotInternal')
                ->line($node->getDocComment()?->getStartLine() ?? 0)
                ->build();
        }

        if ($extension) {
            $errors = array_merge($errors, $this->validateExtension($node));
        }

        return $errors;
    }

    /**
     * @return array<RuleError|string>
     */
    private function validateExtension(InClassNode $node): array
    {
        $errors = [];

        $nameConstant = null;
        try {
            $nameConstant = $node->getClassReflection()->getConstant('NAME');
        } catch (MissingConstantFromReflectionException) {
            $errors[] = RuleErrorBuilder::message('Extension classes should have a public NAME constant')
                ->identifier('cicada.extensionPublicNameConst')
                ->line($node->getStartLine())
                ->build();
        }

        if ($nameConstant && !$nameConstant->isPublic()) {
            $errors[] = RuleErrorBuilder::message('Extension classes should have a public NAME constant')
                ->identifier('cicada.extensionPublicNameConst')
                ->line($node->getStartLine())
                ->build();
        }

        // is final?
        if (!$node->getClassReflection()->isFinal()) {
            $errors[] = RuleErrorBuilder::message('Extension classes should be final')
                ->identifier('cicada.extensionFinal')
                ->line($node->getStartLine())
                ->build();
        }

        $constructor = $node->getClassReflection()->getConstructor();
        $internal = $this->isInternal($constructor->getDocComment() ?? '');
        if (!$internal) {
            $errors[] = RuleErrorBuilder::message('Extension classes constructor should be marked as internal')
                ->identifier('cicada.extensionConstructInternal')
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isInternal(string $doc): bool
    {
        return \str_contains($doc, '@internal') || \str_contains($doc, 'reason:becomes-internal');
    }

    private function isExtension(InClassNode $node): bool
    {
        $reflection = $node->getClassReflection();

        if ($reflection->getParentClass() === null) {
            return false;
        }

        $parentClass = $reflection->getParentClass()->getName();

        return $parentClass === Extension::class;
    }

    private function isExample(InClassNode $node): bool
    {
        $namespace = $node->getClassReflection()->getName();

        return \str_contains($namespace, 'Cicada\\Tests\\Examples\\');
    }
}

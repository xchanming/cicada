<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\SchemaBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\EnumField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Cicada\Core\Framework\Log\Package;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @internal
 *
 * @implements Rule<InClassNode>
 */
#[Package('framework')]
class DALFieldsMustBeRegisteredWithSchemaBuilder implements Rule
{
    use InTestClassTrait;

    /**
     * @var array<class-string, class-string>|null
     */
    private ?array $mappings = null;

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return array<array-key, RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $ref = $scope->getClassReflection();

        if ($ref === null) {
            return [];
        }

        if (!\str_starts_with($ref->getName(), 'Cicada\\Core\\')) {
            return [];
        }

        if (!$ref->isSubclassOf(Field::class)) {
            return [];
        }

        if ($this->isInTestClass($scope)) {
            // if in a test namespace, don't care
            return [];
        }

        if ($this->isFieldIgnored($ref)) {
            return [];
        }

        if (!$this->isRegisteredWithSchemaBuilder($ref->getName())) {
            return [
                RuleErrorBuilder::message(\sprintf('Field %s must be registered with %s', $ref->getName(), SchemaBuilder::class))
                    ->identifier('cicada.dalFieldRegistration')
                    ->build(),
            ];
        }

        return [];
    }

    public function isRegisteredWithSchemaBuilder(string $class): bool
    {
        if (!$this->mappings) {
            $this->mappings = $this->getSchemaBuilderMappings();
        }

        return isset($this->mappings[$class]);
    }

    /**
     * @return array<class-string, class-string>
     */
    public function getSchemaBuilderMappings(): array
    {
        $reflectionProperty = $this->reflectionProvider
            ->getClass(SchemaBuilder::class)
            ->getNativeReflection()
            ->getProperty('fieldMapping');

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue();
    }

    private function isFieldIgnored(ClassReflection $field): bool
    {
        return match (true) {
            $field->is(AssociationField::class),
            $field->is(EnumField::class),
            $field->is(TranslatedField::class) => true,
            default => false,
        };
    }
}

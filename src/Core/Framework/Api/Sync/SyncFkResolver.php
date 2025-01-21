<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Sync;

use Cicada\Core\Framework\Api\ApiException;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
class SyncFkResolver
{
    /**
     * @internal
     *
     * @param iterable<AbstractFkResolver> $resolvers
     */
    public function __construct(
        private readonly DefinitionInstanceRegistry $registry,
        private readonly iterable $resolvers
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     *
     * @return array<int, array<string|int, mixed>>
     */
    public function resolve(string $key, string $entity, array $payload): array
    {
        $map = $this->collect($entity, $payload, $key);

        if (empty($map)) {
            return $payload;
        }

        foreach ($map as $key => &$values) {
            $values = $this->getResolver($key)->resolve($values);
        }

        $exceptions = [];

        \array_walk_recursive($payload, function (&$value) use (&$exceptions): void {
            if (!$value instanceof FkReference) {
                return;
            }

            if ($value->resolved !== null) {
                $value = $value->resolved;

                return;
            }

            if ($value->nullOnMissing) {
                $value = null;

                return;
            }

            $exceptions[] = [
                'pointer' => $value->pointer,
                'entity' => $value->entityName . '.' . $value->fieldName,
            ];
        });

        if (!empty($exceptions)) {
            throw ApiException::canNotResolveForeignKeysException($exceptions);
        }

        return $payload;
    }

    /**
     * @param array<int, array<string|int, mixed>> $payload
     *
     * @return array<string, array<FkReference>>
     */
    private function collect(string $entity, array &$payload, string $pointer): array
    {
        $definition = $this->registry->getByEntityName($entity);

        $map = [];
        foreach ($payload as $key => &$row) {
            $current = implode('/', [$pointer, (string) $key]);

            foreach ($row as $fieldName => &$value) {
                $fieldName = (string) $fieldName;

                if (\is_array($value) && isset($value['resolver']) && isset($value['value'])) {
                    $definition = $this->registry->getByEntityName($entity);

                    $field = $definition->getField($fieldName);

                    if (!$field) {
                        throw ApiException::canNotResolveResolverField($entity, $fieldName);
                    }

                    $ref = match (true) {
                        $field instanceof FkField => $field->getReferenceDefinition()->getEntityName(),
                        $field instanceof IdField => $entity,
                        default => null,
                    };

                    if ($ref === null) {
                        continue;
                    }

                    $resolver = (string) $value['resolver'];

                    $reference = new FkReference(
                        implode('/', [$current, $fieldName]),
                        $definition->getEntityName(),
                        $field->getPropertyName(),
                        $value['value'],
                        $value['nullOnMissing'] ?? false
                    );
                    $row[$fieldName] = $reference;
                    $map[$resolver][] = $reference;
                }

                if (\is_array($value)) {
                    $field = $definition->getField($fieldName);

                    if (!$field instanceof AssociationField) {
                        continue;
                    }

                    $nested = [];
                    if ($field instanceof ManyToManyAssociationField || $field instanceof OneToManyAssociationField) {
                        $ref = $field instanceof ManyToManyAssociationField ? $field->getToManyReferenceDefinition()->getEntityName() : $field->getReferenceDefinition()->getEntityName();
                        $nested = $this->collect($ref, $value, implode('/', [$current, $fieldName]));
                    } elseif ($field instanceof ManyToOneAssociationField || $field instanceof OneToOneAssociationField) {
                        $tmp = [$value];
                        $nested = $this->collect($field->getReferenceDefinition()->getEntityName(), $tmp, implode('/', [$current, $fieldName]));
                        $value = \array_shift($tmp);
                    }

                    $map = $this->merge($map, $nested);
                }
            }
        }

        return $map;
    }

    /**
     * @param array<string, array<FkReference>> $map
     * @param array<string, array<FkReference>> $nested
     *
     * @return array<string, array<FkReference>>
     */
    private function merge(array $map, array $nested): array
    {
        foreach ($nested as $resolver => $values) {
            foreach ($values as $value) {
                $map[$resolver][] = $value;
            }
        }

        return $map;
    }

    private function getResolver(string $key): AbstractFkResolver
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver::getName() === $key) {
                return $resolver;
            }
        }

        throw ApiException::resolverNotFoundException($key);
    }
}

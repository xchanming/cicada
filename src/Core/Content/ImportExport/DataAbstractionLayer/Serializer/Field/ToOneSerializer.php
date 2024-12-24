<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\PrimaryKeyResolver;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('core')]
class ToOneSerializer extends FieldSerializer
{
    /**
     * @internal
     */
    public function __construct(private readonly PrimaryKeyResolver $primaryKeyResolver)
    {
    }

    /**
     * @param mixed $record
     *
     * @return iterable<string, mixed>
     */
    public function serialize(Config $config, Field $toOne, $record): iterable
    {
        if (!$toOne instanceof ManyToOneAssociationField && !$toOne instanceof OneToOneAssociationField) {
            throw new \InvalidArgumentException('Expected *ToOneField');
        }

        if ($record === null) {
            return null;
        }

        if ($record instanceof Struct) {
            $record = $record->jsonSerialize();
        }

        $definition = $toOne->getReferenceDefinition();
        $entitySerializer = $this->serializerRegistry->getEntity($definition->getEntityName());

        $result = $entitySerializer->serialize($config, $definition, $record);
        yield $toOne->getPropertyName() => iterator_to_array($result);
    }

    /**
     * @param mixed $records
     */
    public function deserialize(Config $config, Field $toOne, $records): mixed
    {
        if (!$toOne instanceof ManyToOneAssociationField && !$toOne instanceof OneToOneAssociationField) {
            throw new \InvalidArgumentException('Expected *ToOneField');
        }

        $definition = $toOne->getReferenceDefinition();
        $entitySerializer = $this->serializerRegistry->getEntity($definition->getEntityName());
        /** @var \Traversable<mixed> $records */
        $records = $this->primaryKeyResolver->resolvePrimaryKeyFromUpdatedBy($config, $definition, $records);

        $result = $entitySerializer->deserialize($config, $definition, $records);

        if (!\is_array($result)) {
            $result = iterator_to_array($result);
        }
        if (empty($result)) {
            return null;
        }

        return $result;
    }

    public function supports(Field $field): bool
    {
        return $field instanceof ManyToOneAssociationField || $field instanceof OneToOneAssociationField;
    }
}

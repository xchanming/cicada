<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Pipe;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\AbstractEntitySerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\PrimaryKeyResolver;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class EntityPipe extends AbstractPipe
{
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionInstanceRegistry,
        private readonly SerializerRegistry $serializerRegistry,
        private ?EntityDefinition $definition = null,
        private ?AbstractEntitySerializer $entitySerializer = null,
        private readonly ?PrimaryKeyResolver $primaryKeyResolver = null
    ) {
    }

    /**
     * @param array<mixed> $record
     */
    public function in(Config $config, iterable $record): iterable
    {
        $this->loadConfig($config);

        return $this->entitySerializer->serialize($config, $this->definition, $record);
    }

    public function out(Config $config, iterable $record): iterable
    {
        $this->loadConfig($config);

        if ($this->primaryKeyResolver) {
            $record = $this->primaryKeyResolver->resolvePrimaryKeyFromUpdatedBy($config, $this->definition, $record);
        }

        return $this->entitySerializer->deserialize($config, $this->definition, $record);
    }

    private function loadConfig(Config $config): void
    {
        $this->definition ??= $this->definitionInstanceRegistry->getByEntityName($config->get('sourceEntity') ?? '');

        $this->entitySerializer ??= $this->serializerRegistry->getEntity($this->definition->getEntityName());
    }
}

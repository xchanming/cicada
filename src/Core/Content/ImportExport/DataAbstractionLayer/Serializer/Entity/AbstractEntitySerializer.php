<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Symfony\Contracts\Service\ResetInterface;

#[Package('core')]
abstract class AbstractEntitySerializer implements ResetInterface
{
    protected SerializerRegistry $serializerRegistry;

    /**
     * @param array<mixed>|Struct|null $entity
     *
     * @return \Generator
     */
    abstract public function serialize(Config $config, EntityDefinition $definition, $entity): iterable;

    /**
     * @param array<mixed>|\Traversable<mixed> $entity
     *
     * @return array<mixed>|\Traversable<mixed>
     */
    abstract public function deserialize(Config $config, EntityDefinition $definition, $entity);

    abstract public function supports(string $entity): bool;

    public function setRegistry(SerializerRegistry $serializerRegistry): void
    {
        $this->serializerRegistry = $serializerRegistry;
    }

    public function reset(): void
    {
        $this->getDecorated()->reset();
    }

    protected function getDecorated(): AbstractEntitySerializer
    {
        throw new \RuntimeException('Implement getDecorated');
    }
}

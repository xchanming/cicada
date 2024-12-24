<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class AbstractFieldSerializer
{
    protected SerializerRegistry $serializerRegistry;

    /**
     * @param mixed $value
     *
     * @return iterable<string, mixed>
     */
    abstract public function serialize(Config $config, Field $field, $value): iterable;

    /**
     * @param mixed $value
     */
    abstract public function deserialize(Config $config, Field $field, $value): mixed;

    abstract public function supports(Field $field): bool;

    public function setRegistry(SerializerRegistry $serializerRegistry): void
    {
        $this->serializerRegistry = $serializerRegistry;
    }

    abstract public function getDecorated(): AbstractFieldSerializer;
}

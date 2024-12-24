<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event\EventData;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\FrameworkException;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class EntityType implements EventDataType
{
    final public const TYPE = 'entity';

    /**
     * @var class-string<EntityDefinition>
     */
    private readonly string $definitionClass;

    private readonly string $entityName;

    /**
     * @deprecated tag:v6.7.0 - Will throw an exception if invalid $definitionClass is passed
     *
     * @param class-string<EntityDefinition>|EntityDefinition $definitionClass
     */
    public function __construct(string|EntityDefinition $definitionClass)
    {
        if (Feature::isActive('v6.7.0.0')
            && \is_string($definitionClass)
            && !\is_a($definitionClass, EntityDefinition::class, true)
        ) {
            throw FrameworkException::invalidEventData(
                'Expected an instance of ' . EntityDefinition::class . ' or a class name that extends ' . EntityDefinition::class
            );
        }

        $entityDefinition = $definitionClass instanceof EntityDefinition ? $definitionClass : new $definitionClass();

        $this->definitionClass = $entityDefinition::class;
        $this->entityName = $entityDefinition->getEntityName();
    }

    public function toArray(): array
    {
        return [
            'type' => self::TYPE,
            'entityClass' => $this->definitionClass,
            'entityName' => $this->entityName,
        ];
    }
}

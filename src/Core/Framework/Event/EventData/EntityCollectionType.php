<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event\EventData;

use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
class EntityCollectionType implements EventDataType
{
    final public const TYPE = 'collection';

    public function __construct(private readonly string $definitionClass)
    {
    }

    public function toArray(): array
    {
        return [
            'type' => self::TYPE,
            'entityClass' => $this->definitionClass,
        ];
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event\EventData;

use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
class ArrayType implements EventDataType
{
    final public const TYPE = 'array';

    public function __construct(private readonly EventDataType $type)
    {
    }

    public function toArray(): array
    {
        return [
            'type' => self::TYPE,
            'of' => $this->type->toArray(),
        ];
    }
}

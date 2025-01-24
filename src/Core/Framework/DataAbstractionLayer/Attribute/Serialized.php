<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Attribute;

use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\StringFieldSerializer;
use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Serialized extends Field
{
    public const TYPE = 'serialized';

    public function __construct(
        public string $serializer = StringFieldSerializer::class,
        public bool|array $api = false,
        public bool $translated = false,
        public ?string $column = null
    ) {
        parent::__construct(type: self::TYPE, translated: $translated, api: $api, column: $column);
    }
}

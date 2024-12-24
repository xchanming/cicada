<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Attribute;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class OneToOne extends Field
{
    public const TYPE = 'one-to-one';

    public function __construct(
        public string $entity,
        public ?string $column = null,
        public OnDelete $onDelete = OnDelete::NO_ACTION,
        public string $ref = 'id',
        public bool|array $api = false
    ) {
        parent::__construct(type: self::TYPE, api: $api, column: $column);
    }
}

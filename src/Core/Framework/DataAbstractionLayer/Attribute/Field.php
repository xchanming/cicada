<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Attribute;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Field
{
    public bool $nullable;

    /**
     * @param bool|array{admin-api: bool, store-api: bool} $api
     */
    public function __construct(
        public string $type,
        public bool $translated = false,
        public bool|array $api = false,
        public ?string $column = null,
    ) {
    }
}

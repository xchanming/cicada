<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Attribute;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ReferenceVersion extends Field
{
    public const TYPE = 'reference-version';

    public function __construct(public string $entity, public ?string $column = null)
    {
        parent::__construct(type: self::TYPE, api: true, column: $column);
    }
}

<?php

declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\AssociationField;

/**
 * @internal
 */
class MyAssociationField extends AssociationField
{
    protected function getSerializerClass(): string
    {
        return self::class;
    }
}

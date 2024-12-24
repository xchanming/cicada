<?php

declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;

/**
 * @internal
 */
class NotRegisteredField extends Field
{
    protected function getSerializerClass(): string
    {
        return self::class;
    }
}

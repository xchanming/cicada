<?php

declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\TranslatedField;

/**
 * @internal
 */
class MyTranslatedField extends TranslatedField
{
    protected function getSerializerClass(): string
    {
        return self::class;
    }
}

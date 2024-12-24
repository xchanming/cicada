<?php

declare(strict_types=1);

namespace Cicada\Core\Framework\Test\DataAbstractionLayer\Write\NonUuidFkField;

use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;

/**
 * @internal test class
 */
class NonUuidFkField extends FkField
{
    protected function getSerializerClass(): string
    {
        return NonUuidFkFieldSerializer::class;
    }
}

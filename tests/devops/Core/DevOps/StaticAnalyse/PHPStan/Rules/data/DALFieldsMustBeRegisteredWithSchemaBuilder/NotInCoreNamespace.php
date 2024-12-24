<?php declare(strict_types=1);

namespace Cicada\Core\Test\Field;

use Cicada\SomewhereElse\Framework\DataAbstractionLayer\Field\Field;

/**
 * @internal
 */
class NotInCoreNamespace extends Field
{
    protected function getSerializerClass(): string
    {
        return self::class;
    }
}

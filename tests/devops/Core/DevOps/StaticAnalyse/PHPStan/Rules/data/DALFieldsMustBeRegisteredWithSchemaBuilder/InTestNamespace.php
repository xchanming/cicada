<?php declare(strict_types=1);

namespace Cicada\Core\Test\Field;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;

/**
 * @internal
 */
class InTestNamespace extends Field
{
    protected function getSerializerClass(): string
    {
        return self::class;
    }
}

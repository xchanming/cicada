<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\fixture;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @internal
 *
 * @extends EntityCollection<AttributeEntity>
 */
class AttributeEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AttributeEntity::class;
    }
}

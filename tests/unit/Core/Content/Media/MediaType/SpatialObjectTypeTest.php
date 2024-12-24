<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\MediaType;

use Cicada\Core\Content\Media\MediaType\SpatialObjectType;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(SpatialObjectType::class)]
class SpatialObjectTypeTest extends TestCase
{
    public function testName(): void
    {
        static::assertEquals('SPATIAL_OBJECT', (new SpatialObjectType())->getName());
    }
}

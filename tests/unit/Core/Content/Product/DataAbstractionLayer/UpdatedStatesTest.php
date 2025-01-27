<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\DataAbstractionLayer;

use Cicada\Core\Content\Product\DataAbstractionLayer\UpdatedStates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UpdatedStates::class)]
class UpdatedStatesTest extends TestCase
{
    public function testUpdatedStates(): void
    {
        $updatedStates = new UpdatedStates('foobar', ['foo'], ['bar']);

        static::assertEquals('foobar', $updatedStates->getId());
        static::assertEquals(['foo'], $updatedStates->getOldStates());
        static::assertEquals(['bar'], $updatedStates->getNewStates());

        $updatedStates->setNewStates(['foo']);

        static::assertEquals(['foo'], $updatedStates->getNewStates());
    }
}

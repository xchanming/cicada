<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Struct;

use Cicada\Core\Framework\Struct\CloneTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CloneTrait::class)]
class CloneStructTest extends TestCase
{
    public function testClone(): void
    {
        $nestedStruct = new CloneStruct();
        $nestedStruct->backedEnum = CloneStructBackedEnum::Case;
        $nestedStruct->unitEnum = CloneStructUnitEnum::Case;

        $original = new CloneStruct();
        $original->arrayOfStructs = [$nestedStruct];
        $original->backedEnum = CloneStructBackedEnum::Case;
        $original->nestedStruct = $nestedStruct;
        $original->unitEnum = CloneStructUnitEnum::Case;

        $clone = clone $original;

        static::assertEquals($original, $clone);
        static::assertNotSame($original, $clone);

        static::assertNotSame($original->arrayOfStructs[0], $clone->arrayOfStructs[0]);
        static::assertNotSame($original->nestedStruct, $clone->nestedStruct);
    }
}

/**
 * @internal
 */
class CloneStruct
{
    use CloneTrait;

    /**
     * @var array<array-key, CloneStruct>
     */
    public array $arrayOfStructs;

    public CloneStructBackedEnum $backedEnum;

    public CloneStructUnitEnum $unitEnum;

    public CloneStruct $nestedStruct;
}

/**
 * @internal
 */
enum CloneStructBackedEnum: int
{
    case Case = 1;
}

/**
 * @internal
 */
enum CloneStructUnitEnum
{
    case Case;
}

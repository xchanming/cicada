<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Test\Stub\DataAbstractionLayer;

use Cicada\Core\Test\Stub\DataAbstractionLayer\EmptyEntityExistence;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EmptyEntityExistence::class)]
class EmptyEntityExistenceTest extends TestCase
{
    public function testICanCreateStub(): void
    {
        $stub = new EmptyEntityExistence();
        static::assertEmpty($stub->getEntityName());
    }
}

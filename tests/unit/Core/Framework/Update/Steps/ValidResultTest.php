<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Update\Steps;

use Cicada\Core\Framework\Update\Steps\ValidResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ValidResult::class)]
class ValidResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $result = new ValidResult(1, 2);

        static::assertSame(1, $result->getOffset());
        static::assertSame(2, $result->getTotal());
    }
}

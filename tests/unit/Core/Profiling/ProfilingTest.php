<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Profiling;

use Cicada\Core\Profiling\Profiling;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Profiling::class)]
class ProfilingTest extends TestCase
{
    public function testTemplatePriority(): void
    {
        $profiling = new Profiling();

        static::assertEquals(-2, $profiling->getTemplatePriority());
    }
}

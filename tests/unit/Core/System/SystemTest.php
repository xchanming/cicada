<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System;

use Cicada\Core\System\System;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(System::class)]
class SystemTest extends TestCase
{
    public function testTemplatePriority(): void
    {
        $system = new System();

        static::assertEquals(-1, $system->getTemplatePriority());
    }
}

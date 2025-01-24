<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Log;

use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(Package::class)]
class PackageTest extends TestCase
{
    public function testConstructor(): void
    {
        $package = new Package('framework');
        static::assertSame('framework', $package->package);
    }

    public function testNonExistingClass(): void
    {
        static::assertNull(Package::getPackageName('asdjkfljasdlkfjdas'));
    }

    public function testNoPackageAttribute(): void
    {
        static::assertNull(Package::getPackageName(NoPackage::class));
    }

    public function testPackage(): void
    {
        static::assertSame('framework', Package::getPackageName(WithPackage::class));
    }

    public function testParentPackage(): void
    {
        static::assertSame('framework', Package::getPackageName(WithParentPackage::class, true));
    }

    public function testParentPackageWithoutFlag(): void
    {
        static::assertNull(Package::getPackageName(WithParentPackage::class));
    }
}

/**
 * @internal
 */
class NoPackage
{
}

/**
 * @internal
 */
#[Package('framework')]
class WithPackage
{
}

/**
 * @internal
 */
class WithParentPackage extends WithPackage
{
}

<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig\Extension;

use Cicada\Core\Framework\Adapter\Twig\Extension\InAppPurchaseExtension;
use Cicada\Core\Framework\Test\Store\StaticInAppPurchaseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(InAppPurchaseExtension::class)]
class InAppPurchaseExtensionTest extends TestCase
{
    public function testGetFunctions(): void
    {
        $functions = (new InAppPurchaseExtension(StaticInAppPurchaseFactory::createWithFeatures()))->getFunctions();

        static::assertCount(2, $functions);
        static::assertSame('inAppPurchase', $functions[0]->getName());
        static::assertSame('allInAppPurchases', $functions[1]->getName());
    }

    public function testIsActive(): void
    {
        $extension = new InAppPurchaseExtension(StaticInAppPurchaseFactory::createWithFeatures(['app' => ['test']]));

        static::assertTrue($extension->isActive('app', 'test'));
        static::assertFalse($extension->isActive('app', 'nonexistent'));
    }

    public function testAll(): void
    {
        $extension = new InAppPurchaseExtension(StaticInAppPurchaseFactory::createWithFeatures(['app' => ['test'], 'anotherapp' => ['test2']]));

        static::assertSame(['app-test', 'anotherapp-test2'], $extension->all());
    }
}

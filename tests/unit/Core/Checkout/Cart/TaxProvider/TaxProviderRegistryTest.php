<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\TaxProvider;

use Cicada\Core\Checkout\Cart\TaxProvider\TaxProviderRegistry;
use Cicada\Tests\Unit\Core\Checkout\Cart\TaxProvider\_fixtures\TestConstantTaxRateProvider;
use Cicada\Tests\Unit\Core\Checkout\Cart\TaxProvider\_fixtures\TestEmptyTaxProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TaxProviderRegistry::class)]
class TaxProviderRegistryTest extends TestCase
{
    public function testProviderRegistered(): void
    {
        $registry = new TaxProviderRegistry(
            [new TestConstantTaxRateProvider()]
        );

        static::assertTrue($registry->has(TestConstantTaxRateProvider::class));
        static::assertInstanceOf(TestConstantTaxRateProvider::class, $registry->get(TestConstantTaxRateProvider::class));

        static::assertFalse($registry->has(TestEmptyTaxProvider::class));
    }

    public function testProviderNotFound(): void
    {
        $registry = new TaxProviderRegistry(
            [new TestConstantTaxRateProvider()]
        );

        static::assertFalse($registry->has(TestEmptyTaxProvider::class));
        static::assertNull($registry->get(TestEmptyTaxProvider::class));
    }
}

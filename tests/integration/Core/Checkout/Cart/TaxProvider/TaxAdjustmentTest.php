<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\TaxProvider;

use Cicada\Core\Checkout\Cart\Price\AmountCalculator;
use Cicada\Core\Checkout\Cart\TaxProvider\TaxAdjustment;
use Cicada\Core\Checkout\Cart\TaxProvider\TaxAdjustmentCalculator;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TaxAdjustmentTest extends TestCase
{
    use KernelTestBehaviour;

    public function testUsesCorrectCalculator(): void
    {
        $adjustment = static::getContainer()->get(TaxAdjustment::class);
        $ref = new \ReflectionClass(TaxAdjustment::class);

        static::assertTrue($ref->hasProperty('amountCalculator'));

        $calculator = $ref->getProperty('amountCalculator')->getValue($adjustment);

        static::assertInstanceOf(AmountCalculator::class, $calculator);

        $ref = new \ReflectionClass($calculator);

        static::assertTrue($ref->hasProperty('taxCalculator'));

        $taxCalculator = $ref->getProperty('taxCalculator')->getValue($calculator);

        static::assertInstanceOf(TaxAdjustmentCalculator::class, $taxCalculator);
    }
}

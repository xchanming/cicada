<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Shipping\Cart\Error;

use Cicada\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingMethodBlockedError::class)]
class ShippingMethodBlockedErrorTest extends TestCase
{
    public function testConstruct(): void
    {
        $error = new ShippingMethodBlockedError('FOO');

        static::assertSame('Shipping method FOO not available', $error->getMessage());
        static::assertFalse($error->isPersistent());
        static::assertSame(['name' => 'FOO'], $error->getParameters());
        static::assertSame('FOO', $error->getName());
        static::assertTrue($error->blockOrder());
        static::assertSame('shipping-method-blocked-FOO', $error->getId());
        static::assertSame(10, $error->getLevel());
        static::assertSame('shipping-method-blocked', $error->getMessageKey());
    }
}

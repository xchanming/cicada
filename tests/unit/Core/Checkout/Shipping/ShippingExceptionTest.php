<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Shipping;

use Cicada\Core\Checkout\Shipping\ShippingException;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingException::class)]
class ShippingExceptionTest extends TestCase
{
    public function testShippingMethodNotFound(): void
    {
        $e = new \Exception('bar');

        $exception = ShippingException::shippingMethodNotFound('foo', $e);

        static::assertSame(400, $exception->getStatusCode());
        static::assertSame('CHECKOUT__SHIPPING_METHOD_NOT_FOUND', $exception->getErrorCode());
        static::assertSame('Could not find shipping method with id "foo"', $exception->getMessage());
        static::assertSame($e, $exception->getPrevious());
    }

    public function testDuplicateShippingMethodPrice(): void
    {
        $e = new \Exception('bar');

        $exception = ShippingException::duplicateShippingMethodPrice($e);

        static::assertSame(400, $exception->getStatusCode());
        static::assertSame('CHECKOUT__DUPLICATE_SHIPPING_METHOD_PRICE', $exception->getErrorCode());
        static::assertSame('Shipping method price quantity already exists.', $exception->getMessage());
        static::assertSame($e, $exception->getPrevious());
    }

    public function testDuplicateTechnicalName(): void
    {
        $exception = ShippingException::duplicateTechnicalName('foo');

        static::assertSame(400, $exception->getStatusCode());
        static::assertSame('CHECKOUT__DUPLICATE_SHIPPING_METHOD_TECHNICAL_NAME', $exception->getErrorCode());
        static::assertSame('The technical name "foo" is not unique.', $exception->getMessage());
        static::assertSame(['technicalName' => 'foo'], $exception->getParameters());
    }
}

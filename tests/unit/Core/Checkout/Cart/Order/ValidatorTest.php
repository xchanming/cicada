<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Order;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartValidatorInterface;
use Cicada\Core\Checkout\Cart\Error\Error;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Cart\Validator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[CoversClass(Validator::class)]
class ValidatorTest extends TestCase
{
    public function testValidate(): void
    {
        $mockValidator = $this->createMock(CartValidatorInterface::class);
        $mockValidator2 = new class($this->createMock(Error::class)) implements CartValidatorInterface {
            public function __construct(private readonly Error $error)
            {
            }

            public function validate(
                Cart $cart,
                ErrorCollection $errors,
                SalesChannelContext $context
            ): void {
                $errors->add($this->error);
            }
        };
        $validator = new Validator([$mockValidator, $mockValidator2]);
        $context = $this->createMock(SalesChannelContext::class);
        $cart = new Cart('test');

        $mockValidator->expects(static::once())->method('validate')->with($cart, static::anything(), $context);

        $errors = $validator->validate($cart, $context);
        static::assertCount(1, $errors);
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class Validator
{
    /**
     * @internal
     *
     * @param CartValidatorInterface[] $validators
     */
    public function __construct(private readonly iterable $validators)
    {
    }

    public function validate(Cart $cart, SalesChannelContext $context): array
    {
        $errors = new ErrorCollection();
        foreach ($this->validators as $validator) {
            $validator->validate($cart, $errors, $context);
        }

        return array_values($errors->getElements());
    }
}

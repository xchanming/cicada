<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Exception;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class CartTokenNotFoundException extends CartException
{
}

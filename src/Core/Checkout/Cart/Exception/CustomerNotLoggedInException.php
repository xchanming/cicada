<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Exception;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Framework\Log\Package;

/**
 * @deprecated tag:v6.7.0 - Will be removed
 */
#[Package('checkout')]
class CustomerNotLoggedInException extends CartException
{
}

<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice;

use Cicada\Core\Checkout\Shipping\ShippingException;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class ShippingMethodPriceExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (\preg_match('/SQLSTATE\[23000\]:.*1062 Duplicate.*shipping_method_price.uniq.shipping_method_quantity_start\'/', $e->getMessage())) {
            return ShippingException::duplicateShippingMethodPrice($e);
        }

        return null;
    }
}

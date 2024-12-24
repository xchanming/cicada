<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed. Use OrderException::deliveryWithoutAddress instead
 */
#[Package('checkout')]
class DeliveryWithoutAddressException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Delivery contains no shipping address');
    }

    public function getErrorCode(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'OrderException::deliveryWithoutAddress')
        );

        return 'CHECKOUT__DELIVERY_WITHOUT_ADDRESS';
    }

    public function getStatusCode(): int
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedClassMessage(self::class, 'v6.7.0.0', 'OrderException::deliveryWithoutAddress')
        );

        return Response::HTTP_BAD_REQUEST;
    }
}

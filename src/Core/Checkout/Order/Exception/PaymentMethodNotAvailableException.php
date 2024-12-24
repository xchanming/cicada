<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PaymentMethodNotAvailableException extends CicadaHttpException
{
    public function __construct(string $id)
    {
        parent::__construct(
            'The order has no active payment method - {{ id }}',
            ['id' => $id]
        );
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__UNAVAILABLE_PAYMENT_METHOD';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}

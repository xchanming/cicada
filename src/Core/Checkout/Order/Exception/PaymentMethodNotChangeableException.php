<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class PaymentMethodNotChangeableException extends CicadaHttpException
{
    public function __construct(string $id)
    {
        parent::__construct(
            'The order has an active transaction - {{ id }}',
            ['id' => $id]
        );
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__PAYMENT_METHOD_UNCHANGEABLE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}

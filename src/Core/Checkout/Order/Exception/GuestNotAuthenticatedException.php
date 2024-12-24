<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class GuestNotAuthenticatedException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Guest not authenticated.');
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__GUEST_NOT_AUTHENTICATED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}

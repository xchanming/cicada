<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class WrongGuestCredentialsException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Wrong credentials for guest authentication.');
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__GUEST_WRONG_CREDENTIALS';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class LanguageOfOrderDeleteException extends CicadaHttpException
{
    public function __construct(?\Throwable $e = null)
    {
        parent::__construct('The language is still linked in some orders.', [], $e);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__LANGUAGE_OF_ORDER_DELETE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}

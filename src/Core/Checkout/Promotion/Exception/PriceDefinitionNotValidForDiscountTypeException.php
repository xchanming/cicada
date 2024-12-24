<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('buyers-experience')]
class PriceDefinitionNotValidForDiscountTypeException extends CicadaHttpException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__INVALID_PRICE_DEFINITION_FOR_DISCOUNT_TYPE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}

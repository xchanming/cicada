<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Exception;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('buyers-experience')]
class PriceNotFoundException extends CicadaHttpException
{
    public function __construct(LineItem $item)
    {
        parent::__construct('No calculated price found for item ' . $item->getId());
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__PRICE_NOT_FOUND_FOR_ITEM';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}

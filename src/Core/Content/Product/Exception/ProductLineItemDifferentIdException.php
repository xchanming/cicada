<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('inventory')]
class ProductLineItemDifferentIdException extends CicadaHttpException
{
    public function __construct(string $lineItemId)
    {
        $message = \sprintf('The `productId` and `referencedId` of the line item %s are not identical.', $lineItemId);
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_REFERENCED_ID_DIFFERENT';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}

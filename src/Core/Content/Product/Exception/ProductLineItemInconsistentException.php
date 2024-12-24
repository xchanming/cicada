<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('inventory')]
class ProductLineItemInconsistentException extends CicadaHttpException
{
    public function __construct(string $lineItemId)
    {
        $message = \sprintf(
            'To change the product of line item (%s), the following properties must also be updated: `productId`, `referenceId`, `payload.productNumber`.',
            $lineItemId
        );

        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_LINE_ITEM_INCONSISTENT';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}

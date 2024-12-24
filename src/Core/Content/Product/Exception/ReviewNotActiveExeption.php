<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('inventory')]
class ReviewNotActiveExeption extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Reviews not activated');
    }

    public function getErrorCode(): string
    {
        return 'PRODUCT__REVIEW_NOT_ACTIVE';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}

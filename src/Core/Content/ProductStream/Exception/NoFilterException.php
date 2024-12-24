<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class NoFilterException extends CicadaHttpException
{
    public function __construct(string $id)
    {
        parent::__construct('Product stream with ID {{ id }} has no filters', ['id' => $id]);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_STREAM_MISSING_FILTER';
    }
}

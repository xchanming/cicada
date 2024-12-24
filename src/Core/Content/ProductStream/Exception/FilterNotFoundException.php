<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class FilterNotFoundException extends CicadaHttpException
{
    public function __construct(string $type)
    {
        parent::__construct('Filter for type {{ type}} not found', ['type' => $type]);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_STREAM_FILTER_NOT_FOUND';
    }
}

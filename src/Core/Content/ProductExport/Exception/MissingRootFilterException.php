<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class MissingRootFilterException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Missing root filter ');
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_EXPORT_EMPTY';
    }
}

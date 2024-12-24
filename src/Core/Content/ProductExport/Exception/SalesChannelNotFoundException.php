<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class SalesChannelNotFoundException extends CicadaHttpException
{
    public function __construct(string $id)
    {
        parent::__construct('Sales channel with ID {{ id }} not found', ['id' => $id]);
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_EXPORT_SALES_CHANNEL_NOT_FOUND';
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class NoConfiguratorFoundException extends CicadaHttpException
{
    public function __construct(string $productId)
    {
        parent::__construct(
            'Product with id {{ productId }} has no configuration.',
            ['productId' => $productId]
        );
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__PRODUCT_HAS_NO_CONFIGURATOR';
    }
}

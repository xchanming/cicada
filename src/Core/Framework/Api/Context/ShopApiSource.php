<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Context;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class ShopApiSource extends SalesChannelApiSource
{
    public string $type = 'shop-api';
}

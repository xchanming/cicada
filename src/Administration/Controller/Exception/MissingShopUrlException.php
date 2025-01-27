<?php declare(strict_types=1);

namespace Cicada\Administration\Controller\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class MissingShopUrlException extends CicadaHttpException
{
    public function __construct()
    {
        parent::__construct('Failed to retrieve the shop url.');
    }

    public function getErrorCode(): string
    {
        return 'ADMINISTRATION__MISSING_SHOP_URL';
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class UnknownFileException extends CicadaHttpException
{
    public function getErrorCode(): string
    {
        return 'CONTENT__SITEMAP_UNKNOWN_FILE';
    }
}

<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Core\Params;

use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
enum UrlParamsSource
{
    case MEDIA;
    case THUMBNAIL;
}

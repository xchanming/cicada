<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Cms;

use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class VimeoVideoCmsElementResolver extends YoutubeVideoCmsElementResolver
{
    public function getType(): string
    {
        return 'vimeo-video';
    }
}

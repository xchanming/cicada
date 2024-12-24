---
title:              Optimize thumbnail generation performance
issue:              NEXT-14411
author:             OliverSkroblin
author_email:       o.skroblin@cicada.com
author_github:      @OliverSkroblin
---
# Core
* Added `\Cicada\Core\Content\Media\Thumbnail\ThumbnailService::generate` to generate thumbnails for multiple entities at once
* Deprecated `\Cicada\Core\Content\Media\Thumbnail\ThumbnailService::generateThumbnails`, use `\Cicada\Core\Content\Media\Thumbnail\ThumbnailService::generate` instead

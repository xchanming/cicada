---
title: Add loadMediaFileStream to MediaService and FileLoader
issue: NEXT-21711
author: JoshuaBehrens
author_email: code@joshua-behrens.de
author_github: @JoshuaBehrens
---
# Core
* Added `\Cicada\Core\Content\Media\File\FileLoader::loadMediaFileStream` as companion to `\Cicada\Core\Content\Media\File\FileLoader::loadMediaFile` but it returns a stream for better memory management options with big files
* Added `\Cicada\Core\Content\Media\MediaService::loadFileStream` as companion to `\Cicada\Core\Content\Media\MediaService::loadFile` but it returns a stream for better memory management options with big files

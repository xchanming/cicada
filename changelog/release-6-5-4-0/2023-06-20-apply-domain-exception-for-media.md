---
title: Apply domain exception for media
issue: NEXT-26928
---
# Core
* Added new domain exception class `Cicada\Core\Content\Media\MediaException`.
* Deprecated the following exceptions in replacement for Domain Exceptions:
  * `Cicada\Core\Content\Media\Exception\CouldNotRenameFileException`
  * `Cicada\Core\Content\Media\Exception\DisabledUrlUploadFeatureException`
  * `Cicada\Core\Content\Media\Exception\EmptyMediaFilenameException`
  * `Cicada\Core\Content\Media\Exception\EmptyMediaIdException`
  * `Cicada\Core\Content\Media\Exception\FileExtensionNotSupportedException`
  * `Cicada\Core\Content\Media\Exception\IllegalFileNameException`
  * `Cicada\Core\Content\Media\Exception\IllegalUrlException`
  * `Cicada\Core\Content\Media\Exception\MediaFolderNotFoundException`
  * `Cicada\Core\Content\Media\Exception\MissingFileExtensionException`
  * `Cicada\Core\Content\Media\Exception\StrategyNotFoundException`
  * `Cicada\Core\Content\Media\Exception\StreamNotReadableException`
  * `Cicada\Core\Content\Media\Exception\ThumbnailCouldNotBeSavedException`
  * `Cicada\Core\Content\Media\Exception\ThumbnailNotSupportedException`
  * `Cicada\Core\Content\Media\Exception\UploadException`

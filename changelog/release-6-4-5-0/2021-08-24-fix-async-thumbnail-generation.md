---
title: Fix async thumbnail generation
issue: NEXT-10733
author_github: @Dominik28111
---
# Core
* Changed method `Cicada\Core\Content\Media\DataAbstractionLayer\MediaThumbnailRepositoryDecorator::delete()` to perform file deletion syncronously if context has state `SYNCHRONE_FILE_DELETE` and dispatch message for each filesystem visibility.
* Added method `Cicada\Core\Content\Media\Message\DeleteFileMessage::getVisibility()`.
* Added method `Cicada\Core\Content\Media\Message\DeleteFileMessage::setVisibility()`.
* Changed handler `Cicada\Core\Content\Media\Message\DeleteFileHandler` to consider filesystem visibility.

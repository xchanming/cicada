---
title: Handle exception for VersionManager
issue: NEXT-30181
---
# Core
* Added 2 new exception methods `cannotCreateNewVersion` and `versionMergeAlreadyLocked` in `Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException`
* Added an alternative exception by throwing `DataAbstractionLayerException::cannotCreateNewVersion()` in `cloneEntity` method of `Cicada\Core\Framework\DataAbstractionLayer\VersionManager`
* Added an alternative exception by throwing `DataAbstractionLayerException::versionMergeAlreadyLocked()` in `merge` method of `Cicada\Core\Framework\DataAbstractionLayer\VersionManager`

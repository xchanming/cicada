---
title: Deprecated exceptions and properties due to PHPStan update
issue: NEXT-37561
author: Michael Telgmann
author_github: @mitelg
---
# Core
* Deprecated properties of `\Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity`. They will be typed natively in v6.7.0.0.
* Deprecated properties of `\Cicada\Core\Framework\DataAbstractionLayer\Entity`. They will be typed natively in v6.7.0.0.
* Deprecated properties of `\Cicada\Core\Framework\DataAbstractionLayer\Field\FkField`. They will be typed natively in v6.7.0.0.
* Deprecated properties of `\Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField`. They will be typed natively in v6.7.0.0.
* Deprecated exception `\Cicada\Core\Framework\Api\Exception\UnsupportedEncoderInputException`. It will be removed in v6.7.0.0. Use `\Cicada\Core\Framework\Api\ApiException::unsupportedEncoderInput` instead.
* Deprecated exception `\Cicada\Core\Framework\DataAbstractionLayer\Exception\CanNotFindParentStorageFieldException`. It will be removed in v6.7.0.0. Use `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException::cannotFindParentStorageField` instead.
* Deprecated exception `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`. It will be removed in v6.7.0.0. Use `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException::internalFieldAccessNotAllowed` instead.
* Deprecated exception `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InvalidParentAssociationException`. It will be removed in v6.7.0.0. Use `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException::invalidParentAssociation` instead.
* Deprecated exception `\Cicada\Core\Framework\DataAbstractionLayer\Exception\ParentFieldNotFoundException`. It will be removed in v6.7.0.0. Use `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException::parentFieldNotFound` instead.
* Deprecated exception `\Cicada\Core\Framework\DataAbstractionLayer\Exception\PrimaryKeyNotProvidedException`. It will be removed in v6.7.0.0. Use `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException::primaryKeyNotProvided` instead.
* Deprecated method `\Cicada\Core\Framework\DataAbstractionLayer\Entity::__get`. It will throw a `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException` in v6.7.0.0.
* Deprecated method `\Cicada\Core\Framework\DataAbstractionLayer\Entity::get`. It will throw a `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException` in v6.7.0.0.
* Deprecated method `\Cicada\Core\Framework\DataAbstractionLayer\Entity::checkIfPropertyAccessIsAllowed`. It will throw a `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException` in v6.7.0.0.
* Deprecated method `\Cicada\Core\Framework\DataAbstractionLayer\Entity::get`. It will throw a `\Cicada\Core\Framework\DataAbstractionLayer\Exception\PropertyNotFoundException` instead of a `\InvalidArgumentException` in v6.7.0.0.
___
# Upgrade Information
## Native typehints of properties
The properties of the following classes will be typed natively in v6.7.0.0.
If you have extended from those classes and overwritten the properties, you can already set the correct type.
* `\Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity`
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity`
* `\Cicada\Core\Framework\DataAbstractionLayer\Field\FkField`
* `\Cicada\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField`
## Deprecated exceptions
The following exceptions were deprecated and will be removed in v6.7.0.0.
You can already catch the replacement exceptions additionally to the deprecated ones.
* `\Cicada\Core\Framework\Api\Exception\UnsupportedEncoderInputException`. Also catch `\Cicada\Core\Framework\Api\ApiException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\CanNotFindParentStorageFieldException`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InvalidParentAssociationException`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\ParentFieldNotFoundException`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\PrimaryKeyNotProvidedException`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException`.
## Deprecated methods
The following methods of the `\Cicada\Core\Framework\DataAbstractionLayer\Entity` class were deprecated and will throw different exceptions in v6.7.0.0.
You can already catch the replacement exceptions additionally to the deprecated ones.
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity::__get`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` in addition to `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity::get`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` in addition to `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity::checkIfPropertyAccessIsAllowed`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` in addition to `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity::get`. Also catch `\Cicada\Core\Framework\DataAbstractionLayer\Exception\PropertyNotFoundException` in addition to `\InvalidArgumentException`.
___
# Next Major Version Changes
## Removal of deprecated exceptions
The following exceptions were removed:
* `\Cicada\Core\Framework\Api\Exception\UnsupportedEncoderInputException`
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\CanNotFindParentStorageFieldException`
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InvalidParentAssociationException`
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\ParentFieldNotFoundException`
* `\Cicada\Core\Framework\DataAbstractionLayer\Exception\PrimaryKeyNotProvidedException`
## Entity class throws different exceptions
The following methods of the `\Cicada\Core\Framework\DataAbstractionLayer\Entity` class are now throwing different exceptions:
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity::__get` now throws a `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity::get` now throws a `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity::checkIfPropertyAccessIsAllowed` now throws a `\Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` instead of a `\Cicada\Core\Framework\DataAbstractionLayer\Exception\InternalFieldAccessNotAllowedException`.
* `\Cicada\Core\Framework\DataAbstractionLayer\Entity::get` now throws a `\Cicada\Core\Framework\DataAbstractionLayer\Exception\PropertyNotFoundException` instead of a `\InvalidArgumentException`.

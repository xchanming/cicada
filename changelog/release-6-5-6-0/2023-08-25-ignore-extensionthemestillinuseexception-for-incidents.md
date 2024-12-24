---
title: Ignore ExtensionThemeStillInUseException for incidents
issue: NEXT-30141
author: Sebastian Franze
author_email: s.franze@cicada.com
---
# Core
* Deprecated `Cicada\Core\Framework\Store\Exception\ExtensionThemeStillInUseException`. It will be removed. Use `Cicada\Core\Framework\Store\StoreException::extensionThemeStillInUse` instead.
* Changed class hierarchy of `Cicada\Core\Framework\Store\Exception\ExtensionThemeStillInUseException`. It now extends `Cicada\Core\Framework\Store\StoreException`.
* Changed log level of error code `FRAMEWORK__EXTENSION_THEME_STILL_IN_USE` to notice.
* Deprecated `Cicada\Core\Framework\Store\Exception\ExtensionNotFoundException`. It will be removed.
* Deprecated `Cicada\Core\Framework\Store\Exception\ExtensionNotFoundException::fromTechnicalName`. It will be removed. Use `Cicada\Core\Framework\Store\StoreException::extensionNotFoundFromTechnicalName` instead.
* Deprecated `Cicada\Core\Framework\Store\Exception\ExtensionNotFoundException::fromId`. It will be removed. Use `Cicada\Core\Framework\Store\StoreException::extensionNotFoundFromId` instead.
* Changed class hierarchy of `Cicada\Core\Framework\Store\Exception\ExtensionNotFoundException`.  It now extends `Cicada\Core\Framework\Store\StoreException`.
* Deprecated `Cicada\Core\Framework\Store\Exception\ExtensionUpdateRequiresConsentAffirmationException`. It will be removed.
* Deprecated `Cicada\Core\Framework\Store\Exception\ExtensionUpdateRequiresConsentAffirmationException::fromDelta`. It will be removed. Use `Cicada\Core\Framework\Store\StoreException::extensionUpdateRequiresConsentAffirmationException` instead.
* Changed class hierarchy of `Cicada\Core\Framework\Store\Exception\ExtensionUpdateRequiresConsentAffirmationException`.  It now extends `Cicada\Core\Framework\Store\StoreException`.
* Deprecated `Cicada\Core\Framework\Store\Exception\ExtensionInstallException`. It will be removed. Use `Cicada\Core\Framework\Store\StoreException::extensionInstallException` instead.
* Changed class hierarchy of `Cicada\Core\Framework\Store\Exception\ExtensionInstallException`.  It now extends `Cicada\Core\Framework\Store\StoreException`.

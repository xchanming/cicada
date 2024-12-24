---
title: Add service for determining delta of app permissions and domains
issue: NEXT-18876
author: Frederik Schmitt
author_email: f.schmitt@cicada.com
author_github: fschmtt
---
# Core
* Added abstract class `Cicada\Core\Framework\App\Delta\AbstractAppConfirmationDeltaProvider`
  * Added service `Cicada\Core\Framework\App\Delta\AppConfirmationDeltaProvider`
  * Added service `Cicada\Core\Framework\App\Delta\PermissionsDeltaService`
* Added exception `Cicada\Core\Framework\Store\Exception\ExtensionUpdateRequiresConsentAffirmationException`
* Deprecated exception `Cicada\Core\Framework\Store\Exception\ExtensionRequiresNewPrivilegesException`, will be replaced with `Cicada\Core\Framework\Store\Exception\ExtensionUpdateRequiresConsentAffirmationException`
  * Deprecated `Cicada\Core\Framework\Store\Exception\ExtensionUpdateRequiresConsentAffirmationException` to only extend from `Cicada\Core\Framework\CicadaHttpException`
Changed `Cicada\Core\Framework\App\Lifecycle\Update\AppUpdater` to catch new `Cicada\Core\Framework\Store\Exception\ExtensionUpdateRequiresConsentAffirmationException`
* Deprecated `Cicada\Core\Framework\Store\Services\StoreAppLifecycleService`, will be marked as internal
  * Changed `Cicada\Core\Framework\Store\Services\StoreAppLifecycleService` to use new `Cicada\Core\Framework\App\Delta\AppConfirmationDeltaProvider`
  * Deprecated method `Cicada\Core\Framework\Store\Services\StoreAppLifecycleService::getAppIdByName()`
___
# Next Major Version Changes
## Deprecations in `Cicada\Core\Framework\Store\Services\StoreAppLifecycleService`
The class `StoreAppLifecycleService` has been marked as internal.

We also removed the `StoreAppLifecycleService::getAppIdByName()` method.

## Removal of `Cicada\Core\Framework\Store\Exception\ExtensionRequiresNewPrivilegesException`
We removed the `ExtensionRequiresNewPrivilegesException` exception.
Will be replaced with the internal `ExtensionUpdateRequiresConsentAffirmationException` exception to have a more generic one.

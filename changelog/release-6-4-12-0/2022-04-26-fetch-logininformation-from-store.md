---
title: Fetch logininformation from store
issue: NEXT-20617
author: Sebastian Franze
author_email: s.franze@cicada.com
author_github: Sebastian Franze
---
# Core
* Added migration `Migration1650981517RemoveCicadaId`.
* Removed system config key `core.store.cicadaId`.
* Changed `Cicada\Core\Framework\Store\Services\StoreClient::loginWithCicadaId`. Method will not write system config entry `core.store.cicadaId` anymore.
* Changed `Cicada\Core\Framework\Store\Services\FirstRunWizardClient::frwLogin`. Method will not write system config entry `core.store.cicadaId` anymore.
* Added new parameter `user_info` to `cicada.store_endpoints`.
___
# API
* Changed Response of `/api/_action/store/checklogin`. Response now contains key `userInfo` with information about the sw acount.
___
# Administration
* Added new global types `CicadaHttpError` and `StoreApiException`.
* Added new type `UserInfo` in `src/core/service/api/store.api.service.ts`.
* Changed `src/module/sw-extension/page/sw-extension-my-extensions-account/index.js` to `src/module/sw-extension/page/sw-extension-my-extensions-account/index.ts`.
* Deprecated method `loginCicadaUser` in `src/module/sw-extension/page/sw-extension-my-extensions-account/index.ts`. Use Method `login` instead
* Changed `src/module/sw-extension/service/extension-error-handler.service.js` to `src/module/sw-extension/service/extension-error-handler.service.ts`.
* Added new type `MappedError` in `src/module/sw-extension/service/extension-error-handler.service.ts`.
* Added new field `userInfo` in `CicadaExtensionsState`.
* Added mutation `setUserInfo` in `CicadaExtensionsState`.
* Deprecated field `cicadaId` in `CicadaExtensionsState`. Check existence of `userInfo` instead.
* Deprecated field `loginStatus` in `CicadaExtensionsState` Check existence of `userInfo` instead.
* Deprecated mutation `storeCicadaId` in `CicadaExtensionsState`. Mutation will be removed without replacement.
* Deprecated mutation `setLoginStatus` in `CicadaExtensionsState`. Mutation will be removed without replacement.

---
title: Handle unauthenticated app registration failure
issue: NEXT-20097
author: Frederik Schmitt
author_email: f.schmitt@cicada.com
author_github: fschmtt
---
# Core
* Changed `Cicada\Core\Framework\App\Exception\AppRegistrationException` to extend `Cicada\Core\Framework\CicadaHttpException`
* Added `Cicada\Core\Framework\App\Exception\AppLicenseCouldNotBeVerifiedException`
* Changed `Cicada\Core\Framework\App\Lifecycle\Registration\StoreHandshake::signPayload()` to throw `Cicada\Core\Framework\App\Exception\AppLicenseCouldNotBeVerifiedException`
___
# Administration
* Changed `src/module/sw-extension/service/index.js` to pass error codes to the `ExtensionErrorService` constructor
* Changed `src/module/sw-extension/service/extension-error.service.js` to handle `actions` and `autoClose` correctly for notifications

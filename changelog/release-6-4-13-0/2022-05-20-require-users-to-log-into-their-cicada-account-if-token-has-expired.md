---
title: Require users to log into their Cicada Account if token has expired
issue: NEXT-21092
author: Frederik Schmitt
author_email: f.schmitt@cicada.com
author_github: fschmtt
---
# Core
* Added `Cicada\Core\Framework\Store\Exception\ShopSecretInvalidException`
* Added `Cicada\Core\Framework\Store\Exception\StoreSessionExpiredException`
* Changed `Cicada\Core\Framework\Store\Services\StoreClientFactory` to use new middlewares
* Added `Cicada\Core\Framework\Store\Services\ShopSecretInvalidMiddleware` to log out all users
* Added `Cicada\Core\Framework\Store\Services\StoreSessionExpiredMiddleware` to log out a user if their token has expired
* Added `Cicada\Core\Framework\Store\Services\VerifyResponseSignatureMiddleware`
* Added `Cicada\Core\Framework\Store\Subscriber\LicenseHostChangedSubscriber` to log out all users when the license host changed
___
# API
* Changed multiple store routes to fail with `403 Forbidden` if authentication is required but the user's Cicada Account session has expired
___
# Administration
* Added `storeSessionExpiredInterceptor` to retry requests that previously failed with store exceptions in `src/core/factory/http.factory.js`
* Changed `handleErrorStates` to notify the user if their Cicada Account session has expired in `src/core/factory/http.factory.js`
